<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\User\UserDashboardController;

use App\Livewire\User\QuizTake;
use App\Livewire\User\QuestLocationDashboard;
use App\Livewire\User\GameDashboard;
use App\Livewire\User\QuizResults;
use App\Livewire\User\GameAssessmentForm;
use Ladumor\LaravelPwa\LaravelPwa;
use App\Livewire\Forms\TeamForm;


// PWA Routes
Route::get('/manifest.json', [LaravelPwa::class, 'manifest'])->name('pwa.manifest');
Route::get('/serviceworker.js', [LaravelPwa::class, 'sw'])->name('pwa.sw');
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
        Route::get('/hero-slides', [DashboardController::class, 'heroSlides'])->name('hero-slides');
        
    });
    
    // User-only routes
    Route::middleware(['user'])->group(function () {
        // Team registration route (accessible to users without teams)
        Route::get('/team-registration', TeamForm::class)->name('team.registration');
        
        // Routes that require team registration
        Route::middleware(['team'])->group(function () {
            Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
            Route::middleware('quiz')->group(function () {
                Route::get('/quiz/start/{questionnaireId}', QuizTake::class)->name('quiz.start');
                Route::get('/quiz/take/{questionnaireId}', QuizTake::class)->name('quiz.take');
                Route::get('/quiz/continue/{attemptId}', QuizTake::class)->name('quiz.continue');
                Route::get('/quiz/results/{attemptId}', QuizResults::class)->name('quiz.results');
                Route::get('/game/assessment/{assessmentId}', GameAssessmentForm::class)->name('game.assessment');
            });
            Route::get('/quest-dashboard', QuestLocationDashboard::class)->name('user.quest-location-dashboard');
            Route::get('/game-dashboard', GameDashboard::class)->name('user.game-dashboard');
            Route::get('/test-language', function () {
                return view('test-language');
            })->name('test.language');
        });
    });
});

// Fallback route
Route::fallback(function () {
    return view('livewire.fallback');
});