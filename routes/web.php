<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\PanoramaViewController;

use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\QuestLocationController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\Api\KioskController as ApiKioskController;

use App\Livewire\User\GameDashboard;

use App\Livewire\User\GameAssessmentForm;
use Ladumor\LaravelPwa\LaravelPwa;
use App\Livewire\Forms\TeamForm;


// PWA Routes
Route::get('/manifest.json', [LaravelPwa::class, 'manifest'])->name('pwa.manifest');
Route::get('/serviceworker.js', [LaravelPwa::class, 'sw'])->name('pwa.sw');

// Network connectivity ping endpoint
Route::match(['GET', 'HEAD'], '/ping', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('ping');
// Auth routes
Route::middleware(['guest', 'auth_pages'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
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

// Dark mode test page
Route::get('/test-dark-mode', function () {
    return view('test-dark-mode');
})->name('test.dark-mode');

// Offline functionality test page
Route::get('/test-offline', function () {
    return view('test-offline');
})->name('test.offline');

// Test API endpoint for offline functionality
Route::post('/api/test-submission', function () {
    return response()->json([
        'success' => true,
        'message' => 'Test submission received',
        'timestamp' => now(),
        'offline' => false
    ]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->name('api.test-submission');

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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [DashboardController::class, 'usersManagement'])->name('users');
        Route::get('/quest-locations', [DashboardController::class,'mapManagement'])->name('quest-locations');
        Route::get('/games', [DashboardController::class, 'gameManagement'])->name('games');
        Route::get('/user-progress', [DashboardController::class, 'userProgress'])->name('user-progress');
        Route::get('/user-progress/export', [App\Http\Controllers\Admin\UserProgressExportController::class, 'export'])->name('user-progress.export');
        Route::get('/user-progress/preview', [App\Http\Controllers\Admin\UserProgressExportController::class, 'preview'])->name('user-progress.preview');
        Route::get('/user-progress/download', [App\Http\Controllers\Admin\UserProgressExportController::class, 'download'])->name('user-progress.download');
        Route::post('/user-progress/clear-session', [App\Http\Controllers\Admin\UserProgressExportController::class, 'clearSession'])->name('user-progress.clear-session');
        Route::get('/hero-slides', [DashboardController::class, 'heroSlides'])->name('hero-slides');
        Route::get('/team-management', [DashboardController::class, 'teamManagement'])->name('team-management');
        Route::get('/feature-management', [DashboardController::class, 'featureManagement'])->name('feature-management');
        Route::get('/dashboard-management', [DashboardController::class, 'dashboardManagement'])->name('dashboard-management');
    });
    
    // Panorama viewer routes (admin only)
    Route::middleware(['admin'])->prefix('admin/panorama')->name('panorama.')->group(function () {
        Route::get('/{id}', [PanoramaViewController::class, 'show'])->name('view');
        Route::get('/{gameLocationId}/hotspots', [PanoramaViewController::class, 'getHotspots'])->name('hotspots');
        Route::post('/{gameLocationId}/hotspot', [PanoramaViewController::class, 'addHotspot'])->name('add-hotspot');
        Route::delete('/hotspot/{id}', [PanoramaViewController::class, 'deleteHotspot'])->name('delete-hotspot');
    });
    
    // User-only routes
    Route::middleware(['user'])->group(function () {
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
            Route::get('/panorama/{id}', [App\Http\Controllers\User\UserPanoramaController::class, 'show'])->name('user.panorama.view');
            Route::get('/panorama/{id}/hotspots', [App\Http\Controllers\User\UserPanoramaController::class, 'getHotspots'])->name('user.panorama.hotspots');
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

// API routes for kiosk
Route::prefix('api/kiosk')->name('api.kiosk.')->group(function () {
    Route::get('/leaderboard', [ApiKioskController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/locations', [ApiKioskController::class, 'locations'])->name('locations');
    Route::get('/data', [ApiKioskController::class, 'data'])->name('data');
});

// Live tracking API routes
Route::prefix('api/tracking')->name('api.tracking.')->middleware('auth')->group(function () {
    Route::post('/position', [App\Http\Controllers\Api\LiveTrackingController::class, 'updatePosition'])->name('update-position');
});

Route::prefix('api/live')->name('api.live.')->group(function () {
    Route::get('/positions', [App\Http\Controllers\Api\LiveTrackingController::class, 'getCurrentPositions'])->name('positions');
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