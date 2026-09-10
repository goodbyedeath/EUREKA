<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GpsTrackingController;
use App\Http\Controllers\DebugController;

use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\QuestLocationController;
use App\Http\Controllers\User\UserGpsController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\Api\KioskController as ApiKioskController;

use App\Livewire\User\GameDashboard;

use App\Livewire\User\GameAssessmentForm;
use App\Livewire\Forms\TeamForm;


// PWA Routes
//
// `/manifest.json` is shadowed by the static file of the same name in public/ — the web
// server answers that before Laravel is ever reached — so the route below could never
// change the installed app's icon. The live manifest is served under a name no file
// occupies, and the layouts point at it. The static file stays where it is because both
// service workers precache `/manifest.json` by that exact path.
// Penjelasan umum untuk klien, sengaja tanpa login: halaman ini dibuat untuk dikirimkan.
// Isinya tidak memuat data acara, nama peserta, maupun perolehan nilai — hanya penjelasan
// mengenai bagaimana rangkaian acara berlangsung. Panduan operasional untuk admin ada di
// /admin/panduan dan tetap tertutup.
Route::get('/informasi', fn () => view('informasi-acara'))->name('informasi');

Route::get('/manifest.webmanifest', function () {
    // Each entry is a real square of exactly the size it declares, drawn from the
    // uploaded logo. Declaring 512x512 while serving a 447x558 file made Chrome
    // discard the icon and refuse to offer the install prompt at all.
    //
    // Only `purpose: any`. A maskable icon must fill its whole square with a
    // background of its own, and guessing that colour turns a white logo invisible;
    // left alone, Android draws its own backdrop, which never fails badly.
    $icons = collect(\App\Services\BrandIconService::SIZES)
        ->map(fn ($px) => [
            'src' => \App\Services\BrandIconService::url($px),
            'sizes' => $px . 'x' . $px,
            'type' => 'image/png',
            'purpose' => 'any',
        ])->values();

    return response()->json([
        'name' => \App\Models\BrandSetting::appName(),
        // Home screens give a name about 12 characters. Cutting mid-word produced
        // "Questerra Se"; falling back to the first word reads as a name.
        'short_name' => \Illuminate\Support\Str::length($name = \App\Models\BrandSetting::appName()) <= 12
            ? $name
            : \Illuminate\Support\Str::limit(\Illuminate\Support\Str::before($name, ' '), 12, ''),
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => '#6777ef',
        'theme_color' => '#6777ef',
        'description' => trim(\App\Models\BrandSetting::appName() . ' ' . \App\Models\BrandSetting::tagline()),
        'lang' => 'en',
        'dir' => 'ltr',
        'icons' => $icons,
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest.live');

// Removed: the laravel-pwa package's /manifest.json and /serviceworker.js routes.
// Both answered HTTP 500, and nothing referenced either: the static public/manifest.json
// shadows the first anyway, and the app registers its own /sw.js, not /serviceworker.js.
// The live manifest is the /manifest.webmanifest route above.

// Network connectivity ping endpoint
Route::match(['GET', 'HEAD'], '/ping', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('ping');
// Auth routes with rate limiting
Route::middleware(['guest', 'auth_pages'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Public landing page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }
    
    $heroSlides = \App\Models\HeroSlide::getActiveSlides();
    return view('landing-dynamic', compact('heroSlides'));
})->name('home');

// Removed: /test-offline and its companion /api/test-submission.
//
// The endpoint was unauthenticated and CSRF-exempt, and existed only so the test page
// could POST at it. Both were development scaffolding; neither belongs on a host that is
// about to run a live event. The offline behaviour they exercised is covered by the
// service worker and /api/v1/offline/manifest.

// Debug routes (only for authenticated users)
Route::middleware(['auth'])->prefix('debug')->group(function () {
    Route::get('/quest-health', [DebugController::class, 'questSystemHealth'])->name('debug.quest-health');
    Route::get('/test-database', [DebugController::class, 'testDatabase'])->name('debug.test-database');
    Route::post('/clear-logs', [DebugController::class, 'clearLogs'])->name('debug.clear-logs');
    Route::get('/quest-dashboard', function () {
        return view('debug.quest-dashboard');
    })->name('debug.quest-dashboard');
});

// Authenticated routes
Route::middleware(['auth', 'preventbackhistory'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    });
    
    // Admin-only routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // Bare /admin 404'd, which is the URL people actually type and bookmark.
        Route::redirect('/', '/admin/dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [DashboardController::class, 'usersManagement'])->name('users');
        Route::get('/quest-locations', [DashboardController::class,'mapManagement'])->name('quest-locations');
        Route::get('/games', [DashboardController::class, 'gameManagement'])->name('games');
        // Live desk for indoor outposts: crew radio in, an admin opens the 3D camera.
        Route::get('/outpost-access', fn () => view('admin.outpost-access'))->name('outpost-access');
        // Top-view venue plan with the outposts marked; the indoor stand-in for GPS.
        Route::get('/indoor-maps', fn () => view('admin.indoor-maps'))->name('indoor-maps');
        // The start line for both modes; an outdoor event has one too.
        Route::get('/race-start', fn () => view('admin.race-start'))->name('race-start');
        Route::get('/user-progress', [DashboardController::class, 'userProgress'])->name('user-progress');
        Route::get('/user-progress/export', [App\Http\Controllers\Admin\UserProgressExportController::class, 'export'])->name('user-progress.export');
        Route::get('/user-progress/preview', [App\Http\Controllers\Admin\UserProgressExportController::class, 'preview'])->name('user-progress.preview');
        Route::get('/user-progress/download', [App\Http\Controllers\Admin\UserProgressExportController::class, 'download'])->name('user-progress.download');
        Route::post('/user-progress/clear-session', [App\Http\Controllers\Admin\UserProgressExportController::class, 'clearSession'])->name('user-progress.clear-session');
        Route::get('/hero-slides', [DashboardController::class, 'heroSlides'])->name('hero-slides');
        Route::get('/team-management', [DashboardController::class, 'teamManagement'])->name('team-management');
        Route::get('/gps-tracking', [GpsTrackingController::class, 'index'])->name('gps-tracking');
        Route::get('/feature-management', [DashboardController::class, 'featureManagement'])->name('feature-management');
        Route::get('/dashboard-management', [DashboardController::class, 'dashboardManagement'])->name('dashboard-management');
        // Facilitator scoring for fun_game answers. Used to be a tab inside the page above,
        // which left it with no menu entry of its own.
        Route::get('/game-assessments', fn () => view('admin.game-assessments'))->name('game-assessments');
        // Panduan setup, ditulis untuk operator acara dan bukan untuk programmer.
        Route::get('/panduan', fn () => view('admin.guide'))->name('guide');
    });
    
    
    // team view share one controller; a location without a model falls back to the
    Route::middleware(['admin'])->prefix('admin/ar')->name('ar.')->group(function () {
        Route::get('/{id}', [\App\Http\Controllers\ArExperienceController::class, 'show'])->name('view');
        // On-site authoring: stand at the outpost, point the phone, tap to place.
        Route::post('/{id}/hotspot', [\App\Http\Controllers\ArExperienceController::class, 'storeHotspot'])->name('hotspot.store');
        Route::post('/{id}/media', [\App\Http\Controllers\ArExperienceController::class, 'storeMedia'])->name('media.store');
        // Records where the outpost physically is, captured while authoring on site.
        Route::post('/{id}/location', [\App\Http\Controllers\ArExperienceController::class, 'bindLocation'])->name('location.bind');
        Route::patch('/{id}/hotspot/{hotspotId}', [\App\Http\Controllers\ArExperienceController::class, 'updateHotspot'])->name('hotspot.update');
        Route::delete('/{id}/hotspot/{hotspotId}', [\App\Http\Controllers\ArExperienceController::class, 'destroyHotspot'])->name('hotspot.destroy');
    });

    // User-only routes
    // NOTE: 'user' is registered BOTH as a middleware alias (UserMiddleware) and as a
    // middleware group in bootstrap/app.php. The alias wins, so the group — which carried
    // session.timeout — silently never ran. List them explicitly instead.
    Route::middleware(['user', 'session.timeout', 'access.window'])->group(function () {

        // Deliberately OUTSIDE the [team] group: a leader downloads offline resources on
        // first login, before their team exists. Inside it, EnsureTeamRegistration answers
        // with a redirect to /team-registration and the fetch gets HTML instead of JSON.
        Route::get('/api/offline/manifest', [\App\Http\Controllers\Api\OfflineController::class, 'manifest'])->name('user.offline.manifest');

        Route::get('/ar/{id}', [\App\Http\Controllers\ArExperienceController::class, 'show'])->name('user.ar.view');

        // Indoor navigation. No id means "the event's map", which is the normal case.
        // The indoor opening sequence: START scan, clue, then the plan.
        Route::get('/race/start/{code}', [\App\Http\Controllers\RaceController::class, 'start'])->name('user.race.start');
        Route::get('/race/clue/{map}', [\App\Http\Controllers\RaceController::class, 'clue'])->name('user.race.clue');
        Route::post('/race/clue/{map}', [\App\Http\Controllers\RaceController::class, 'answerClue'])->name('user.race.clue.answer');

        Route::get('/indoor-map/{id?}', [\App\Http\Controllers\IndoorMapController::class, 'show'])->name('user.indoor-map');
        Route::get('/api/indoor-map/{id?}', [\App\Http\Controllers\IndoorMapController::class, 'apiShow'])->name('user.indoor-map.api');

        // JSON scene for clients that render AR themselves (the Android client).
        // Same shape the web viewer gets — both come from placeHotspot().
        Route::get('/api/ar/locations/{id}', [\App\Http\Controllers\ArExperienceController::class, 'apiShow'])->name('user.ar.api');
        // Team registration route (accessible to users without teams)
        Route::get('/team-registration', TeamForm::class)->name('team.registration');
        
        // Routes that require team registration
        Route::middleware(['team'])->group(function () {
            Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
            Route::middleware('quiz')->group(function () {
                Route::get('/quiz/start/{questionnaireId}', [App\Http\Controllers\User\UserQuizController::class, 'start'])->name('quiz.start');
                Route::get('/quiz/take/{questionnaireId}', [App\Http\Controllers\User\UserQuizController::class, 'start'])->name('quiz.take');
                Route::get('/quiz/continue/{attemptId}', [App\Http\Controllers\User\UserQuizController::class, 'continue'])->name('quiz.continue');
                Route::get('/quiz/results/{attemptId}', [App\Http\Controllers\User\UserQuizController::class, 'results'])->name('quiz.results');
                Route::get('/game/assessment/{assessmentId}', GameAssessmentForm::class)->name('game.assessment');
            });
            Route::get('/quest-locations', [QuestLocationController::class, 'index'])->name('user.quest-locations');
            Route::get('/quest-locations/dashboard', [QuestLocationController::class, 'dashboardContent'])->name('user.quest-locations.dashboard');

            // User GPS Tracking (self-location only)
            Route::get('/gps-tracking', [UserGpsController::class, 'index'])->name('user.gps-tracking');

            // Quest Location API routes
            Route::post('/quest-locations/checkin', [QuestLocationController::class, 'checkIn'])->name('user.quest-locations.checkin');
            Route::get('/api/quest-locations', [QuestLocationController::class, 'getLocations'])->name('user.quest-locations.api.list');

            Route::post('/api/quest-locations/update-location', [QuestLocationController::class, 'updateLocation'])->name('user.quest-locations.api.update-location');
            Route::post('/api/quest-locations/get-route', [QuestLocationController::class, 'getRoute'])->name('user.quest-locations.api.route');

            // QR Scanner API routes
            Route::post('/api/qr-scanner/lookup', [App\Http\Controllers\Api\QRScannerController::class, 'lookup'])->name('api.qr-scanner.lookup');

            // Quiz API routes
            Route::get('/api/quiz/start/{questionnaireId}', [App\Http\Controllers\Api\QuizController::class, 'start'])->name('api.quiz.start');
            Route::get('/api/quiz/continue/{attemptId}', [App\Http\Controllers\Api\QuizController::class, 'continue'])->name('api.quiz.continue');
            Route::post('/api/quiz/save-answer', [App\Http\Controllers\Api\QuizController::class, 'saveAnswer'])->name('api.quiz.save-answer');
            Route::post('/api/quiz/submit', [App\Http\Controllers\Api\QuizController::class, 'submit'])->name('api.quiz.submit');
            Route::get('/api/quiz/timer/{attemptId}', [App\Http\Controllers\Api\QuizController::class, 'timer'])->name('api.quiz.timer');
            Route::post('/api/quiz/complete-game', [App\Http\Controllers\Api\QuizController::class, 'completeGame'])->name('api.quiz.complete-game');

            Route::get('/game-dashboard', GameDashboard::class)->name('user.game-dashboard');
            Route::get('/test-language', function () {
                return view('test-language');
            })->name('test.language');
        });
    });
});

// Kiosk routes (public access)
Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/leaderboard', [KioskController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/map', [KioskController::class, 'map'])->name('map');
    Route::get('/led', [KioskController::class, 'led'])->name('led');
});

// API routes for kiosk with rate limiting
Route::prefix('api/kiosk')->name('api.kiosk.')->middleware('throttle:kiosk')->group(function () {
    Route::get('/leaderboard', [ApiKioskController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/locations', [ApiKioskController::class, 'locations'])->name('locations');
    Route::get('/data', [ApiKioskController::class, 'data'])->name('data');
});

// Live tracking API routes
Route::prefix('api/tracking')->name('api.tracking.')->middleware(['auth', 'access.window', 'throttle:tracking'])->group(function () {
    Route::post('/position', [App\Http\Controllers\Api\LiveTrackingController::class, 'updatePosition'])->name('update-position');
});

Route::prefix('api/live')->name('api.live.')->group(function () {
    // Writing a position must be authenticated — this group had no middleware at all.
    Route::post('/position', [App\Http\Controllers\Api\LiveTrackingController::class, 'updatePosition'])
        ->middleware(['auth', 'access.window', 'throttle:tracking'])->name('update-position');

    // Reading stays public on purpose: the kiosk big screen is itself a public page
    // and polls this (kiosk/led.blade.php). Do not add auth here without changing that
    // page first. It is throttled like the rest of the kiosk surface, though — it hands
    // out team names and live coordinates, so it should not be free to scrape at speed.
    Route::get('/positions', [App\Http\Controllers\Api\LiveTrackingController::class, 'getCurrentPositions'])
        ->middleware('throttle:kiosk')->name('positions');
});

// API routes for user features
Route::prefix('api/user')->name('api.user.')->middleware('auth')->group(function () {
    Route::get('/feature-status', [App\Http\Controllers\Api\FeatureController::class, 'checkFeatureStatus'])->name('feature-status');
});


// Ultra Simple Session Management

// Fallback route
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
