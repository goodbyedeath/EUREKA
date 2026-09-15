<?php

namespace App\Livewire\Admin;

use App\Models\LoginCard;
use App\Services\LoginCardService;
use Livewire\Component;
use Livewire\WithPagination;

/** Generate team accounts with printed QR login cards; rotate or revoke a card. */
class LoginCardManager extends Component
{
    use WithPagination;

    public $count = 10;

    public string $prefix = 'Tim';

    /** Access window per account in hours, from its first login. Empty = no limit. */
    public $hours = 8;

    public function generate(): void
    {
        $this->validate([
            'count' => 'required|integer|min:1|max:200',
            'prefix' => 'required|string|max:40',
            'hours' => 'nullable|integer|min:1|max:240',
        ], [
            'count.max' => 'Maksimal 200 kartu sekali generate.',
        ]);

        $cards = app(LoginCardService::class)->generate((int) $this->count, $this->prefix, $this->hours ? (int) $this->hours : null, auth()->user());

        session()->flash('cards_msg', "{$cards->count()} akun tim dan kartu login dibuat (batch {$cards->first()->batch}). Unduh PDF untuk dicetak.");
        $this->resetPage();
    }

    public function rotate(int $id): void
    {
        app(LoginCardService::class)->rotate(LoginCard::with('user')->findOrFail($id));
        session()->flash('cards_msg', 'Kartu diganti: kartu lama tidak berlaku dan akun dikeluarkan dari semua perangkat. Cetak ulang kartu ini.');
    }

    public function toggleRevoke(int $id): void
    {
        $card = LoginCard::with('user')->findOrFail($id);
        app(LoginCardService::class)->setRevoked($card, $card->revoked_at === null);
    }

    public function render()
    {
        return view('livewire.admin.login-card-manager', [
            'cards' => LoginCard::with(['user:id,name,email,team_id,is_active,session_timeout,session_expired_at', 'user.team:id,name'])
                ->orderByDesc('id')->paginate(50),
            'batches' => LoginCard::whereNull('revoked_at')->selectRaw('batch, count(*) as n')->groupBy('batch')->orderByDesc('batch')->pluck('n', 'batch'),
        ])->layout(null);
    }
}
