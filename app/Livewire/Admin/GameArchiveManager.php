<?php

namespace App\Livewire\Admin;

use App\Models\GameArchive;
use App\Services\GameArchiveService;
use Livewire\Component;

/**
 * Archive the running game session and list past archives. A page, not a modal: the admin needs
 * the preview (teams, unfinished posts, live sessions) on screen while confirming.
 */
class GameArchiveManager extends Component
{
    public string $name = '';

    public string $notes = '';

    public string $confirmText = '';

    public function archive()
    {
        $word = GameArchiveService::CONFIRM_WORD;

        $this->validate([
            'name' => 'required|string|min:3|max:150',
            'notes' => 'nullable|string|max:2000',
            'confirmText' => 'required|in:'.$word,
        ], [
            'confirmText.required' => "Ketik {$word} untuk mengonfirmasi.",
            'confirmText.in' => "Ketik {$word} untuk mengonfirmasi.",
        ]);

        $service = app(GameArchiveService::class);
        $preview = $service->preview();

        if ($preview['teams']->isEmpty() && $preview['accounts'] === 0) {
            $this->addError('name', 'Tidak ada tim atau akun pemain untuk diarsipkan.');

            return null;
        }

        try {
            $archive = $service->archive(trim($this->name), trim($this->notes) ?: null, auth()->user());
        } catch (\Throwable $e) {
            report($e);
            $this->addError('name', 'Arsip gagal; tidak ada data yang diubah. '.$e->getMessage());

            return null;
        }

        session()->flash('success', "Sesi \"{$archive->name}\" diarsipkan: {$archive->team_count} tim, {$archive->account_count} akun pemain ditutup. Ganti PIN fasilitator sebelum sesi berikutnya.");

        return $this->redirect(route('admin.game-archives.show', $archive->id));
    }

    public function render()
    {
        return view('livewire.admin.game-archive-manager', [
            'preview' => app(GameArchiveService::class)->preview(),
            'archives' => GameArchive::orderByDesc('archived_at')
                ->get(['id', 'name', 'team_count', 'account_count', 'winner_name', 'winner_score', 'archived_at']),
            'confirmWord' => GameArchiveService::CONFIRM_WORD,
        ])->layout(null);
    }
}
