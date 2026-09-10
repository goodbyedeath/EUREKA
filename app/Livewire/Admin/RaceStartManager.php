<?php

namespace App\Livewire\Admin;

use App\Models\IndoorMap;
use App\Models\RaceSession;
use App\Models\RaceStart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * The start line, for both modes.
 *
 * One code per event. Link it to an indoor plan and the scan leads to that venue's clue;
 * leave it unlinked and it is an outdoor start — the clock begins and the team carries on
 * to the GPS map. The clock stops the same way either way: automatically, once the last
 * post that counts toward finishing is cleared.
 */
class RaceStartManager extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $code = '';
    public ?int $indoorMapId = null;
    public bool $isActive = true;

    /** The printable page for one code. Its own state, so it never sits over the list. */
    public bool $showQr = false;
    public ?int $qrId = null;

    public function openForm(?int $id = null): void
    {
        $this->showQr = false;
        $this->reset(['editingId', 'name', 'code', 'indoorMapId', 'isActive']);
        $this->isActive = true;
        $this->resetValidation();

        if ($id && $start = RaceStart::find($id)) {
            $this->editingId = $start->id;
            $this->name = $start->name;
            $this->code = $start->code;
            $this->indoorMapId = $start->indoor_map_id;
            $this->isActive = $start->is_active;
        } else {
            // A blank field invites someone to type "START" and print it, so offer a real
            // code up front and let them accept it.
            $this->code = RaceStart::generateCode();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function generate(): void
    {
        $this->code = RaceStart::generateCode();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:race_starts,code,' . ($this->editingId ?? 'NULL'),
            'indoorMapId' => 'nullable|exists:indoor_maps,id',
            'isActive' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'indoor_map_id' => $this->indoorMapId ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            RaceStart::findOrFail($this->editingId)->update($data);
        } else {
            RaceStart::create($data + ['created_by' => Auth::id()]);
        }

        $this->showForm = false;
        session()->flash('race_msg', __('Start code saved.'));
    }

    // ------------------------------------------------------------------ QR

    public function openQr(int $id): void
    {
        $this->showForm = false;
        $this->qrId = $id;
        $this->showQr = true;
    }

    public function closeQr(): void
    {
        $this->showQr = false;
        $this->qrId = null;
    }

    /**
     * The QR carries the bare code string, nothing else.
     *
     * `Api\QRScannerController` matches what was scanned against `race_starts.code`
     * directly, so wrapping it in a URL would simply fail to match. It also means a team
     * whose camera will not focus can type the code in by hand.
     */
    public function qrSvg(int $size = 260): string
    {
        $start = $this->qrStart();

        return $start
            ? (string) QrCode::size($size)->margin(2)->errorCorrection('M')->generate($start->code)
            : '';
    }

    public function downloadQr(string $format = 'png', int $size = 1024)
    {
        $start = $this->qrStart();

        if (! $start) {
            return null;
        }

        // 1024px so it survives being blown up to A4 at the start line; error correction M
        // keeps it readable through the tape and creases a printed sheet picks up.
        $image = QrCode::format($format)
            ->size($size)
            ->margin(4)
            ->errorCorrection('M')
            ->generate($start->code);

        $filename = 'start-' . Str::slug($start->name ?: $start->code) . '.' . $format;

        return response()->streamDownload(
            fn () => print($image),
            $filename,
            ['Content-Type' => $format === 'svg' ? 'image/svg+xml' : 'image/png'],
        );
    }

    private function qrStart(): ?RaceStart
    {
        return $this->qrId ? RaceStart::find($this->qrId) : null;
    }

    public function toggle(int $id): void
    {
        $start = RaceStart::find($id);
        $start?->update(['is_active' => ! $start->is_active]);
    }

    public function delete(int $id): void
    {
        // Sessions keep their row; the FK nulls, so a finished run's time is not lost
        // because someone tidied up the code afterwards.
        RaceStart::find($id)?->delete();
        session()->flash('race_msg', __('Start code removed.'));
    }

    public function render()
    {
        return view('livewire.admin.race-start-manager', [
            'qrStart' => $this->qrStart(),
            'starts' => RaceStart::with('indoorMap:id,name')->orderBy('name')->get(),
            'maps' => IndoorMap::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            // How many teams are running or have finished on each code — the only way to
            // tell, before an event, whether a code has already been used in anger.
            'usage' => RaceSession::selectRaw('race_start_id, count(*) as n')
                ->whereNotNull('race_start_id')
                ->groupBy('race_start_id')
                ->pluck('n', 'race_start_id'),
        ]);
    }
}
