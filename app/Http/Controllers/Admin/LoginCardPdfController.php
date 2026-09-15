<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginCard;
use App\Services\LoginCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Printable login cards: all active, one batch (?batch=), or one card (?card=, even if revoked). */
class LoginCardPdfController extends Controller
{
    public function __invoke(Request $request, LoginCardService $service)
    {
        $query = LoginCard::with('user:id,name,email')->orderBy('id');

        if ($request->filled('card')) {
            $query->whereKey((int) $request->input('card'));
        } else {
            $query->whereNull('revoked_at')
                ->when($request->filled('batch'), fn ($q) => $q->where('batch', $request->string('batch')));
        }

        $cards = $query->get()->filter(fn ($c) => $c->user)->map(fn (LoginCard $c) => [
            'name' => $c->user->name,
            'email' => $c->user->email,
            'password' => $c->plainPassword(),
            'qr' => $service->qrDataUri($c),
            'batch' => $c->batch,
        ])->values();

        abort_if($cards->isEmpty(), 404);

        $file = 'kartu-login-'.($request->filled('card') ? 'kartu-'.(int) $request->input('card') : ($request->input('batch') ?: 'semua')).'.pdf';

        return Pdf::loadView('pdf.login-cards', [
            'cards' => $cards,
            'brand' => \App\Models\BrandSetting::appName(),
        ])->setPaper('a4', 'portrait')
            ->setOption('isFontSubsettingEnabled', true)
            ->download($file);
    }
}
