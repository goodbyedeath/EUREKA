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

    /**
     * The number the first card of the batch carries. Empty = continue after the highest card with
     * this prefix — which is what the crew almost always wants, and what the old code only appeared
     * to do: it counted every card ever printed, so a batch of ten came out numbered 04 to 13
     * (operator, 23 Sep). The numbers are read off paper and called over a radio, so they matter.
     */
    public $startAt = '';

    public function generate(): void
    {
        $this->validate([
            'count' => 'required|integer|min:1|max:200',
            'prefix' => 'required|string|max:40',
            'hours' => 'nullable|integer|min:1|max:240',
            'startAt' => 'nullable|integer|min:1|max:999',
        ], [
            'count.max' => 'Maksimal 200 kartu sekali generate.',
            'startAt.max' => 'Nomor awal maksimal 999.',
        ]);

        $cards = app(LoginCardService::class)->generate(
            (int) $this->count,
            $this->prefix,
            $this->hours ? (int) $this->hours : null,
            auth()->user(),
            $this->startAt === '' ? null : (int) $this->startAt,
        );

        session()->flash('cards_msg', sprintf(
            '%d akun tim dan kartu login dibuat: %s sampai %s (batch %s). Unduh PDF untuk dicetak.',
            $cards->count(), $cards->first()->user?->name, $cards->last()->user?->name, $cards->first()->batch,
        ));
        $this->startAt = '';
        $this->resetPage();
    }

    /** Throw away a card that should not have been printed, with the account behind it. */
    public function deleteCard(int $id): void
    {
        $card = LoginCard::with(['user.team'])->findOrFail($id);
        $name = $card->user?->name ?? "kartu #{$card->id}";

        if ($reason = app(LoginCardService::class)->delete($card)) {
            session()->flash('cards_error', $reason);

            return;
        }

        session()->flash('cards_msg', "Kartu {$name} dan akunnya dihapus.");
        $this->resetPage();
    }

    /**
     * The action waiting for a yes, as {kind, id, name}.
     *
     * These three used to ask with `wire:confirm`, which is the browser's own dialog — and a browser
     * that has been shown several dialogs offers "prevent this page from creating additional
     * dialogs". Once that is ticked, confirm() answers no by itself and the button goes dead with no
     * sign of why (operator, 24 Sep: "ganti kartu dan cabut tidak berfungsi"). Asking inside the page
     * cannot be silenced, and it is the same repair the account list already had.
     *
     * @var array{kind: string, id: int, name: string}|null
     */
    public ?array $pending = null;

    public function ask(string $kind, int $id): void
    {
        $card = LoginCard::with('user')->find($id);

        if ($card) {
            $this->pending = ['kind' => $kind, 'id' => $id, 'name' => $card->user?->name ?? '#'.$card->id];
        }
    }

    public function cancel(): void
    {
        $this->pending = null;
    }

    /** Carry out whatever was asked, once. */
    public function confirmPending(): void
    {
        $pending = $this->pending;
        $this->pending = null;

        if (! $pending) {
            return;
        }

        match ($pending['kind']) {
            'rotate' => $this->rotate($pending['id']),
            'revoke' => $this->toggleRevoke($pending['id']),
            'delete' => $this->deleteCard($pending['id']),
            default => null,
        };
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
            // Shown as the placeholder, so the admin can see what "kosong" would give them.
            'nextNumber' => app(LoginCardService::class)->nextNumberFor($this->prefix),
        ])->layout(null);
    }
}
