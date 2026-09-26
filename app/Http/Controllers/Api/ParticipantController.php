<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerParticipant;
use App\Services\PartnerConnector;
use Illuminate\Http\Request;

/**
 * Team setup's "already registered" option: find a participant of the connected partner by name or
 * e-mail. Empty — `directory_disabled` — while no partner is connected, which is the normal state between
 * events (operator, 26 Sep).
 * E-mails come back masked — a team needs to recognise a colleague, not download the guest list.
 */
class ParticipantController extends Controller
{
    public function search(Request $request, PartnerConnector $partner)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        if (! $partner->active()) {
            return response()->json([
                'success' => false,
                'error' => 'directory_disabled',
                'message' => 'Daftar peserta sedang tidak aktif. Tambahkan anggota secara manual.',
            ], 409);
        }

        $like = '%'.addcslashes(trim($data['q']), '%_\\').'%';

        $rows = PartnerParticipant::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'results' => $rows->map(fn (PartnerParticipant $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->maskedEmail(),
                // Always null: the app shows no participant photos (operator, 19 Sep), so the
                // Google avatar URL is no longer sent. Kept as a key for builds that still read it.
                'avatar' => null,
                'available' => $p->team_id === null,
                'team_name' => $p->team_id ? $p->team_name : null,
            ])->values(),
        ]);
    }
}
