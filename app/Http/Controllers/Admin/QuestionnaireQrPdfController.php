<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Services\QuestionnaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Every questionnaire QR in one PDF, to print for the stations.
 *
 * ?layout=grid (default) — cards two across, for a quick check sheet.
 * ?layout=large          — one big QR per A4 page, to tape up at the post.
 * ?all=1                 — include switched-off questionnaires (default: active only).
 */
class QuestionnaireQrPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $large = $request->input('layout') === 'large';

        $questionnaires = Questionnaire::with(['questLocation:id,name', 'gameLocation:id,name'])
            ->when(! $request->boolean('all'), fn ($q) => $q->where('is_active', true))
            ->orderBy('title')
            ->get();

        abort_if($questionnaires->isEmpty(), 404);

        $cards = $questionnaires->map(function (Questionnaire $q) use ($large) {
            QuestionnaireService::ensureQrCode($q);   // old rows can lack a code
            $q->refresh();

            $png = QrCode::format('png')->size($large ? 1200 : 480)->margin(1)->errorCorrection('M')->generate($q->qr_code);

            return [
                'title' => $q->title,
                'code' => $q->qr_code,
                'active' => (bool) $q->is_active,
                'post' => match (true) {
                    $q->venue_mode === 'outdoor' && $q->questLocation => 'Outdoor · '.$q->questLocation->name,
                    $q->venue_mode === 'indoor' && $q->gameLocation => 'Indoor · '.$q->gameLocation->name,
                    default => null,
                },
                'qr' => 'data:image/png;base64,'.base64_encode((string) $png),
            ];
        });

        return Pdf::loadView('pdf.questionnaire-qr', [
            'cards' => $cards,
            'large' => $large,
            'brand' => \App\Models\BrandSetting::appName(),
        ])->setPaper('a4', 'portrait')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('qr-kuesioner-'.($large ? 'besar' : 'grid').'.pdf');
    }
}
