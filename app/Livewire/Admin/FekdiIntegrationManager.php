<?php

namespace App\Livewire\Admin;

use App\Models\FekdiParticipant;
use App\Services\FekdiIntegration;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/** FEKDI x IFSE integration: the switch, the member import, and every participant's point sync. */
class FekdiIntegrationManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';   // all | linked | attention

    public function toggle(): void
    {
        $fekdi = app(FekdiIntegration::class);
        $fekdi->setEnabled(! $fekdi->enabled(), auth()->id());

        session()->flash('fekdi_msg', $fekdi->enabled()
            ? 'Integrasi FEKDI dinyalakan.'
            : 'Integrasi FEKDI dimatikan: daftar peserta disembunyikan dari APK dan tidak ada poin yang dikirim.');
    }

    public function importNow(): void
    {
        try {
            $r = app(FekdiIntegration::class)->import();
            session()->flash('fekdi_msg', "Data peserta ditarik: {$r['seen']} dibaca, {$r['new']} baru, total {$r['total']} peserta.");
        } catch (\Throwable $e) {
            report($e);
            session()->flash('fekdi_error', 'Gagal menarik data peserta: '.$e->getMessage());
        }
    }

    public function syncNow(): void
    {
        $fekdi = app(FekdiIntegration::class);
        $changed = $fekdi->refreshPoints();
        $r = $fekdi->sendIncreases();

        session()->flash('fekdi_msg', isset($r['skipped'])
            ? "Poin diperbarui untuk {$changed} peserta. Pengiriman dilewati: integrasi mati."
            : "Poin diperbarui untuk {$changed} peserta · terkirim {$r['sent']} · gagal {$r['failed']} · perlu dicek {$r['uncertain']}.");
    }

    /** The admin checked the client: the increase did arrive. */
    public function markSynced(int $id): void
    {
        $p = FekdiParticipant::findOrFail($id);
        $p->update([
            'lifetime_sent' => $p->lifetime_sent + max(0, $p->points - $p->points_synced),
            'points_synced' => max($p->points, $p->points_synced),
            'sync_state' => 'idle',
            'sync_error' => null,
            'last_synced_at' => now(),
        ]);
    }

    /** The admin checked the client: nothing arrived — send again. */
    public function retry(int $id): void
    {
        FekdiParticipant::whereKey($id)->update(['sync_state' => 'idle', 'sync_error' => null]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $fekdi = app(FekdiIntegration::class);
        $term = trim($this->search);

        $rows = FekdiParticipant::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('team_name', 'like', "%{$term}%")))
            ->when($this->filter === 'linked', fn ($q) => $q->whereNotNull('team_name'))
            ->when($this->filter === 'attention', fn ($q) => $q->where(fn ($w) => $w
                ->whereIn('sync_state', ['uncertain', 'error'])
                ->orWhereColumn('points', '<', 'points_synced')))
            ->orderByRaw('team_name IS NULL')
            ->orderBy('team_name')
            ->orderBy('name')
            ->paginate(50);

        return view('livewire.admin.fekdi-integration-manager', [
            'enabled' => $fekdi->enabled(),
            'configured' => $fekdi->configured(),
            'host' => parse_url((string) config('services.fekdi.base_url'), PHP_URL_HOST),
            'heartbeat' => \App\Models\AppSetting::row('scheduler_heartbeat')?->value,
            'stats' => [
                'participants' => FekdiParticipant::count(),
                'linked' => FekdiParticipant::whereNotNull('team_id')->count(),
                'pending' => (int) FekdiParticipant::whereColumn('points', '>', 'points_synced')->sum(DB::raw('points - points_synced')),
                'attention' => FekdiParticipant::whereIn('sync_state', ['uncertain', 'error'])->count(),
                'decreases' => FekdiParticipant::whereColumn('points', '<', 'points_synced')->count(),
                'last_import' => FekdiParticipant::max('imported_at'),
                'last_sync' => FekdiParticipant::max('last_synced_at'),
            ],
            'rows' => $rows,
        ])->layout(null);
    }
}
