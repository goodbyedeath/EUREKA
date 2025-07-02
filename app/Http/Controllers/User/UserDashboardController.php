<?php
// app/Http/Controllers/User/DashboardController.php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\HasFeatureAccess;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    use HasFeatureAccess;
    public function index()
    {
        $user = Auth::user();

        // ⛔ Pastikan hanya user role 'user' yang boleh akses
        if ($user->role !== 'user') {
            abort(403, 'Unauthorized access.');
        }

        // ✅ Ambil tim berdasarkan relasi createdTeam (1 user = 1 team)
        $team = $user->createdTeam;

        // ⛔ Optional: kalau team null, redirect balik (harusnya sudah dicegah middleware kamu)
        if (!$team) {
            return redirect()->route('team.registration')
                ->with('info', 'Silakan buat tim terlebih dahulu.');
        }

        // Get enabled features for the dashboard
        $features = $this->getFeatureDashboardData();

        return view('user.dashboard', compact('team', 'features'));
    }

   
}
