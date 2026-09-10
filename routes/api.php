<?php

use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Native client API (v1)
|--------------------------------------------------------------------------
|
| Versioned and separate from the /api/* routes declared in web.php, which the
| PWA reaches with a session cookie. Nothing here replaces those — the browser
| keeps working exactly as it did.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // throttle:auth adds what the controller's own limiter cannot: a ceiling per address.
    // TokenController caps 5 attempts per email+IP, so one address could still try five
    // passwords against unlimited accounts. The web login has had both halves since
    // 2026-09-09; this is the same protection for the app.
    Route::post('/auth/login', [TokenController::class, 'login'])
        ->middleware('throttle:auth')->name('auth.login');

    // Public, because the app paints its splash and login screen before anyone has a
    // token — and the web landing page already serves both to anonymous visitors.
    // Throttled like the other open endpoints so they cannot be scraped at speed.
    Route::middleware('throttle:kiosk')->group(function () {
        Route::get('/branding', [\App\Http\Controllers\Api\AppConfigController::class, 'branding'])->name('branding');
        Route::get('/hero-slides', [\App\Http\Controllers\Api\AppConfigController::class, 'heroSlides'])->name('hero-slides');
    });

    // `throttle:api` was defined but attached to nothing, so the token API had no limit at
    // all — a looping client could hammer it until the host stepped in and took the whole
    // site down with it. The limiter is keyed on the **user**, not the IP: at a venue every
    // team shares one WiFi address, and a per-IP budget divides among all of them.
    Route::middleware(['auth:sanctum', 'access.window', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [TokenController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [TokenController::class, 'me'])->name('auth.me');

        // The AR scene, same shape the web viewer receives.
        Route::get('/ar/locations/{id}', [\App\Http\Controllers\ArExperienceController::class, 'apiShow'])
            ->name('ar.location');

        // Everything a client caches before losing signal.
        // Indoor navigation: a picture of the venue with the outposts marked.
        Route::get('/indoor-map/{id?}', [\App\Http\Controllers\IndoorMapController::class, 'apiShow'])
            ->name('indoor-map');

        Route::get('/offline/manifest', [\App\Http\Controllers\Api\OfflineController::class, 'manifest'])
            ->name('offline.manifest');

        // Game loop.
        Route::post('/qr/lookup', [\App\Http\Controllers\Api\QRScannerController::class, 'lookup'])->name('qr.lookup');
        Route::get('/quiz/start/{questionnaireId}', [\App\Http\Controllers\Api\QuizController::class, 'start'])->name('quiz.start');
        Route::get('/quiz/continue/{attemptId}', [\App\Http\Controllers\Api\QuizController::class, 'continue'])->name('quiz.continue');
        Route::post('/quiz/save-answer', [\App\Http\Controllers\Api\QuizController::class, 'saveAnswer'])->name('quiz.save-answer');
        Route::get('/quiz/timer/{attemptId}', [\App\Http\Controllers\Api\QuizController::class, 'timer'])->name('quiz.timer');
        Route::post('/quiz/submit', [\App\Http\Controllers\Api\QuizController::class, 'submit'])->name('quiz.submit');

        // Facilitator-scored questions. Without this the app can start and submit a quiz
        // but cannot finish a `fun_game` question, which leaves the attempt unfinishable —
        // the web side has had it since the beginning and v1 simply never got the twin.
        Route::post('/quiz/complete-game', [\App\Http\Controllers\Api\QuizController::class, 'completeGame'])->name('quiz.complete-game');

        // Which menus the app should show. A flag decides visibility only; every route
        // stays guarded by middleware, so ignoring one gains a client nothing.
        Route::get('/features', [\App\Http\Controllers\Api\AppConfigController::class, 'features'])->name('features');

        // The race clock. The web flow starts it with a 302 to an HTML page, which a
        // native client cannot follow — these answer in the body instead.
        Route::post('/race/start', [\App\Http\Controllers\RaceController::class, 'apiStart'])->name('race.start');
        Route::get('/race/status', [\App\Http\Controllers\RaceController::class, 'apiStatus'])->name('race.status');
        Route::post('/race/clue/{map}', [\App\Http\Controllers\RaceController::class, 'apiAnswerClue'])->name('race.clue');

        // Location.
        // throttle:tracking, not the group's throttle:api — 30/min per player rather than
        // 120. A live map needs a fix every few seconds at most, and this is the one endpoint
        // a client can be tempted to call as fast as the GPS produces one.
        Route::post('/tracking/position', [\App\Http\Controllers\Api\LiveTrackingController::class, 'updatePosition'])
            ->middleware('throttle:tracking')->name('tracking.position');
        Route::post('/quest-locations/checkin', [\App\Http\Controllers\User\QuestLocationController::class, 'checkIn'])->name('quest.checkin');
        // The player's own team: naming it, filling it, and reading its score. Team
        // registration used to exist only as a Livewire page, which a native client cannot
        // reach — so the app could log a player in and then strand them with no way to form
        // a team. Rules mirror App\Livewire\Forms\TeamForm exactly.
        Route::get('/team', [\App\Http\Controllers\Api\TeamController::class, 'show'])->name('team.show');
        Route::post('/team', [\App\Http\Controllers\Api\TeamController::class, 'store'])->name('team.store');
        Route::post('/team/members', [\App\Http\Controllers\Api\TeamController::class, 'addMember'])->name('team.members.add');
        Route::delete('/team/members/{member}', [\App\Http\Controllers\Api\TeamController::class, 'removeMember'])->name('team.members.remove');

        Route::get('/quest-locations', [\App\Http\Controllers\User\QuestLocationController::class, 'getLocations'])->name('quest.list');

        // Walking directions to an outpost. Needs OPENROUTE_API_KEY in .env; without it the
        // endpoint answers success:false rather than failing loudly.
        Route::post('/quest-locations/get-route', [\App\Http\Controllers\User\QuestLocationController::class, 'getRoute'])->name('quest.route');
    });
});
