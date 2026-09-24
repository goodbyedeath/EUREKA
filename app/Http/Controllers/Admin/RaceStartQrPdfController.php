<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Models\RaceStart;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Every START code in one PDF, to print before the event.
 *
 * Operator, 24 Sep: ten routes means ten START codes, and printing them one browser sheet at a time
 * is ten trips through a dialog. The route each code leads to is printed **large**, because handing
 * a team the wrong START code sends it round the venue in the wrong order — the one mistake this
 * sheet exists to prevent.
 *
 * ?layout=grid (default) — two per row, a check sheet for the crew's table.
 * ?layout=large          — one A4 page per code, to hand out or tape to a start table.
 * ?all=1                 — include switched-off codes (default: active only).
 */
class RaceStartQrPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $large = $request->input('layout') === 'large';

        $starts = RaceStart::with('indoorMap:id,name,clue_question')
            ->when(! $request->boolean('all'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        abort_if($starts->isEmpty(), 404);

        $cards = $starts->map(function (RaceStart $start) use ($large) {
            // The payload is the bare code, exactly what the on-screen sheet encodes and what the
            // app posts to /race/start. Anything else here would print unscannable paper.
            $png = QrCode::format('png')->size($large ? 1200 : 480)->margin(1)->errorCorrection('M')
                ->generate($start->code);

            return [
                'name' => $start->name,
                'code' => $start->code,
                'active' => (bool) $start->is_active,
                'route' => $start->indoor_map_id ? ($start->indoorMap->name ?? 'denah terhapus') : null,
                'clue' => $start->indoorMap?->clue_question,
                'qr' => 'data:image/png;base64,'.base64_encode((string) $png),
            ];
        });

        return Pdf::loadView('pdf.race-start-qr', [
            'cards' => $cards,
            'large' => $large,
            'brand' => BrandSetting::appName(),
        ])->setPaper('a4', 'portrait')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('qr-start-'.($large ? 'besar' : 'grid').'.pdf');
    }
}
