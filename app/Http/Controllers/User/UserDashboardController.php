<?php
// app/Http/Controllers/User/DashboardController.php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\HasFeatureAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserDashboardController extends Controller
{
    use HasFeatureAccess;
    public function index()
    {
        try {
            $user = Auth::user();

            // ⛔ Pastikan hanya user role 'user' yang boleh akses
            if ($user->role !== 'user') {
                abort(403, 'Unauthorized access.');
            }

            // ✅ Try to get team from multiple sources
            $team = $user->team ?? $user->createdTeam;

            // ⛔ If no team found, redirect to team registration
            if (!$team) {
                return redirect()->route('team.registration')
                    ->with('info', 'Silakan buat tim terlebih dahulu.');
            }

            // Get enabled features for the dashboard - with error handling
            try {
                $features = $this->getFeatureDashboardData();
            } catch (\Exception $e) {
                Log::error('Error getting feature dashboard data: ' . $e->getMessage());
                $features = []; // Default empty array if features fail
            }

            // Ensure required features exist
            $this->ensureRequiredFeatures();

            return view('user.dashboard', compact('team', 'features'));
            
        } catch (\Exception $e) {
            Log::error('User dashboard error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'error' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('team.registration')
                ->with('error', 'There was an error loading the dashboard. Please try again.');
        }
    }

    /**
     * Ensure required features exist in the database
     */
    private function ensureRequiredFeatures()
    {
        try {
            $requiredFeatures = [
                'user_dashboard_session_timer' => [
                    'feature_name' => 'Session Timer',
                    'description' => 'Display session timeout timer on user dashboard',
                    'is_enabled' => true,
                    'sort_order' => 7,
                    'metadata' => json_encode([
                        'icon' => 'fas fa-clock',
                        'color' => 'blue',
                        'route' => null
                    ])
                ]
            ];

            foreach ($requiredFeatures as $featureKey => $featureData) {
                $exists = \App\Models\FeatureSetting::where('feature_key', $featureKey)->exists();
                if (!$exists) {
                    \App\Models\FeatureSetting::create(array_merge(['feature_key' => $featureKey], $featureData));
                    Log::info("Created missing feature: {$featureKey}");
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error ensuring required features: ' . $e->getMessage());
        }
    }
}
