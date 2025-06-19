<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\User;
use App\Models\QuizAttempt;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_questionnaires' => Questionnaire::count(),
            'active_questionnaires' => Questionnaire::where('is_active', true)->count(),
            'total_users' => User::where('role', 'user')->count(),
            'total_quiz_attempts' => QuizAttempt::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

     public function usersManagement()
    {
        return view('admin.user-management');
    }

    public function mapManagement()
    {
        return view('admin.map-management');
    }

    public function userProgress()
    {
        return view('admin.user-progress');
    }

    public function heroSlides()
    {
        return view('admin.hero-slides');
    }
}