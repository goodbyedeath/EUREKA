<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\FekdiParticipant;
use App\Models\Team;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * FEKDI x IFSE integration, used 24–27 Sep 2026 only (operator, 15 Sep).
 *
 * 1. Import: the client's member list → fekdi_participants (Google ID, name, email).
 * 2. Team setup in the app: each member is typed in, or picked from that list.
 * 3. Points: every picked member carries their team's total score (PointsCalculationService::teamScore).
 *    The increase is sent live to the client's leaderboard as offline ("luring") points.
 *
 * The client's API can only ADD points and has no duplicate protection (its own docs). So:
 * - only increases are sent (operator, 15 Sep); a drop — penalty, correction, emergency stop — is
 *   recorded and shown to the admin, never sent;
 * - each row remembers the total the client accepted (points_synced) and sends the difference;
 * - a send whose outcome is unknown (timeout, 5xx) is marked `uncertain` and NOT retried, because a
 *   retry could add the points twice. An admin checks the client and resolves it.
 *
 * Everything that talks to the client stops while the admin switch is off.
 */
class FekdiIntegration
{
    public const SETTING = 'fekdi_enabled';

    public function __construct(private PointsCalculationService $points)
    {
    }

    public function enabled(): bool
    {
        return AppSetting::row(self::SETTING)?->value === '1';
    }

    public function configured(): bool
    {
        return filled(config('services.fekdi.base_url')) && filled(config('services.fekdi.api_key'));
    }

    /** The switch is on and there is somewhere to send to. */
    public function active(): bool
    {
        return $this->enabled() && $this->configured();
    }

    public function setEnabled(bool $on, ?int $adminId): void
    {
        AppSetting::put(self::SETTING, $on ? '1' : '0', $adminId);
    }

    /** Pull every page of the client's member list. @return array{seen:int, new:int, total:int} */
    public function import(): array
    {
        $page = 1;
        $seen = 0;
        $new = 0;

        do {
            $response = $this->http()->get('/api/v1/members', ['page' => $page, 'limit' => 100]);
            if (! $response->successful()) {
                throw new \RuntimeException("Member list page {$page} answered HTTP {$response->status()}.");
            }
            $body = $response->json();

            foreach ($body['data'] ?? [] as $m) {
                if (empty($m['id'])) {
                    continue;
                }
                $row = FekdiParticipant::firstOrNew(['google_id' => (string) $m['id']]);
                $new += $row->exists ? 0 : 1;
                $row->fill([
                    'name' => $m['name'] ?? null,
                    'email' => isset($m['email']) ? strtolower(trim($m['email'])) : null,
                    'avatar' => $m['avatar'] ?? null,
                    'joined_at' => ! empty($m['joined_at']) ? Carbon::parse($m['joined_at']) : null,
                    'imported_at' => now(),
                ])->save();
                $seen++;
            }

            $last = (int) ($body['meta']['last_page'] ?? $page);
            $page++;
        } while ($page <= $last && $page <= 1000);

        return ['seen' => $seen, 'new' => $new, 'total' => FekdiParticipant::count()];
    }

    /** Every registered participant's points = their team's current total. @return int rows changed */
    public function refreshPoints(): int
    {
        $changed = 0;
        $teamIds = FekdiParticipant::whereNotNull('team_id')->distinct()->pluck('team_id');

        foreach (Team::whereIn('id', $teamIds)->get() as $team) {
            $total = $this->points->teamScore($team)['total'];
            $changed += FekdiParticipant::where('team_id', $team->id)
                ->where(fn ($q) => $q->where('points', '!=', $total)->orWhere('team_name', '!=', $team->name))
                ->update(['points' => $total, 'team_name' => $team->name, 'updated_at' => now()]);
        }

        return $changed;
    }

    /** Send what each participant gained since the client last accepted. @return array<string,int|string> */
    public function sendIncreases(int $limit = 50): array
    {
        if (! $this->active()) {
            return ['skipped' => 'inactive'];
        }

        // A run that died mid-send left rows claimed; their outcome is unknown.
        FekdiParticipant::where('sync_state', 'sending')->where('updated_at', '<', now()->subMinutes(10))
            ->update(['sync_state' => 'uncertain', 'sync_error' => 'Pengiriman terputus; cek saldo di website klien.']);

        $sent = $failed = $uncertain = 0;
        $rows = FekdiParticipant::whereColumn('points', '>', 'points_synced')
            ->where('sync_state', 'idle')
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $p) {
            $delta = $p->points - $p->points_synced;

            // Claim the row: an overlapping run must not send the same increase.
            $claimed = FekdiParticipant::whereKey($p->id)->where('sync_state', 'idle')
                ->where('points_synced', $p->points_synced)
                ->update(['sync_state' => 'sending', 'updated_at' => now()]);
            if (! $claimed) {
                continue;
            }
            // The claim went through the query builder; reload so the model knows the row is 'sending'.
            // Otherwise setting it back to 'idle' below is not "dirty" and never reaches the database.
            $p->refresh();

            try {
                $response = $this->http()->post('/api/v1/points', [
                    'member_google_id' => $p->google_id,
                    'tipe' => 'offline',
                    'jumlah' => $delta,
                    'note' => "Questerra · {$p->team_name} · total tim {$p->points}",
                    'admin_name' => config('services.fekdi.admin_name', 'Questerra'),
                ]);
            } catch (ConnectionException $e) {
                $p->update(['sync_state' => 'uncertain', 'sync_error' => 'Tidak ada balasan dari website klien: '.$e->getMessage()]);
                $uncertain++;

                continue;
            }

            if ($response->successful()) {
                $p->update([
                    'points_synced' => $p->points_synced + $delta,
                    'lifetime_sent' => $p->lifetime_sent + $delta,
                    'sync_state' => 'idle',
                    'sync_error' => null,
                    'last_synced_at' => now(),
                ]);
                $sent++;
            } elseif ($response->status() === 429) {
                $p->update(['sync_state' => 'idle']);   // nothing was added; try next minute
                break;
            } elseif ($response->serverError()) {
                $p->update(['sync_state' => 'uncertain', 'sync_error' => "HTTP {$response->status()} — mungkin sudah tercatat di website klien."]);
                $uncertain++;
            } else {
                $p->update(['sync_state' => 'error', 'sync_error' => "HTTP {$response->status()}: ".mb_substr($response->body(), 0, 300)]);
                $failed++;
            }
        }

        return compact('sent', 'failed', 'uncertain');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.fekdi.base_url'), '/'))
            ->withHeaders(['X-API-KEY' => (string) config('services.fekdi.api_key')])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }
}
