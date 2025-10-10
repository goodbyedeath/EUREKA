<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Team;
use App\Services\PointsCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class UserProgressExportController extends Controller
{
    public function preview(Request $request)
    {
        // Get filters from session
        $filters = session('export_filters', [
            'selectedTimeframe' => '30',
            'selectedTeam' => 'all',
            'selectedRole' => 'user',
            'searchTerm' => '',
        ]);

        // Get user progress data
        $users = $this->getUserProgressData($filters);
        
        // Get team name for display
        $teamName = null;
        if ($filters['selectedTeam'] !== 'all') {
            $team = Team::find($filters['selectedTeam']);
            $teamName = $team ? $team->name : 'Unknown Team';
        }
        
        // Calculate summary statistics
        $stats = $this->calculateSummaryStats($users);
        
        // Get additional analytics data
        $analyticsData = $this->getAnalyticsData($users, $filters);

        // Return the preview view (web version of the PDF)
        return view('exports.user-progress-preview', [
            'users' => $users,
            'filters' => $filters,
            'teamName' => $teamName,
            'stats' => $stats,
            'analytics' => $analyticsData,
        ]);
    }

    public function export(Request $request)
    {
        try {
            // Get filters from session
            $filters = session('export_filters', [
                'selectedTimeframe' => '30',
                'selectedTeam' => 'all',
                'selectedRole' => 'user',
                'searchTerm' => '',
            ]);

            // Get user progress data
            $users = $this->getUserProgressData($filters);
            
            if ($users->isEmpty()) {
                return redirect()->back()->with('error', 'No user data found for the selected filters.');
            }
            
            // Get team name for display
            $teamName = null;
            if ($filters['selectedTeam'] !== 'all') {
                $team = Team::find($filters['selectedTeam']);
                $teamName = $team ? $team->name : 'Unknown Team';
            }
            
            // Calculate summary statistics
            $stats = $this->calculateSummaryStats($users);
            
            // Get additional analytics data (simplified)
            $analyticsData = $this->getAnalyticsData($users, $filters);

            // Generate PDF with enhanced options
            $pdf = Pdf::loadView('exports.user-progress-pdf', [
                'users' => $users,
                'filters' => $filters,
                'teamName' => $teamName,
                'stats' => $stats,
                'analytics' => $analyticsData,
                'generatedAt' => now(),
            ]);

            // Basic PDF configuration
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = 'eureka-user-progress-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            // Don't clear session data yet - keep for potential download
            // session()->forget('export_filters');

            // Stream PDF for preview in browser (inline display)
            return response()->stream(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"', // inline instead of attachment
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]
            );

        } catch (\Exception $e) {
            \Log::error('PDF Export failed: ' . $e->getMessage(), [
                'filters' => $filters ?? [],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to generate PDF report: ' . $e->getMessage());
        }
    }

    public function download(Request $request)
    {
        try {
            // Get filters from session (same as preview)
            $filters = session('export_filters', [
                'selectedTimeframe' => '30',
                'selectedTeam' => 'all',
                'selectedRole' => 'user',
                'searchTerm' => '',
            ]);

            // Get user progress data
            $users = $this->getUserProgressData($filters);
            
            if ($users->isEmpty()) {
                return redirect()->back()->with('error', 'No user data found for the selected filters.');
            }
            
            // Get team name for display
            $teamName = null;
            if ($filters['selectedTeam'] !== 'all') {
                $team = Team::find($filters['selectedTeam']);
                $teamName = $team ? $team->name : 'Unknown Team';
            }
            
            // Calculate summary statistics
            $stats = $this->calculateSummaryStats($users);
            
            // Get additional analytics data
            $analyticsData = $this->getAnalyticsData($users, $filters);

            // Generate PDF
            $pdf = Pdf::loadView('exports.user-progress-pdf', [
                'users' => $users,
                'filters' => $filters,
                'teamName' => $teamName,
                'stats' => $stats,
                'analytics' => $analyticsData,
                'generatedAt' => now(),
            ]);

            // Configure PDF options
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $teamSlug = $filters['selectedTeam'] !== 'all' ? '-' . Str::slug($teamName ?? 'team') : '';
            $filename = 'eureka-user-progress' . $teamSlug . '-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            // Clear session data after download
            session()->forget('export_filters');

            // Force download
            return response()->stream(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"', // attachment for download
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]
            );
            
        } catch (\Exception $e) {
            \Log::error('PDF Download failed: ' . $e->getMessage(), [
                'filters' => $filters ?? [],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to download PDF report: ' . $e->getMessage());
        }
    }

    public function clearSession(Request $request)
    {
        // Clear the export filters session when leaving preview
        session()->forget('export_filters');
        
        return redirect()->route('admin.user-progress');
    }

    private function getUserProgressData($filters)
    {
        $query = User::with(['quizAttempts' => function($q) use ($filters) {
            $q->where('created_at', '>=', now()->subDays($filters['selectedTimeframe']))
              ->with(['userAnswers.question:id,points', 'questionnaire.questions:id,questionnaire_id,points', 'gameAssessments:id,quiz_attempt_id,user_id,is_assessed,notes,total_deposit,assessed_at']);
        }, 'team:id,name,initial_points,points']);

        // Apply filters
        if ($filters['selectedTeam'] !== 'all') {
            $query->where('team_id', $filters['selectedTeam']);
        }

        if ($filters['selectedRole'] !== 'all') {
            $query->where('role', $filters['selectedRole']);
        } else {
            $query->where('role', 'user');
        }
        
        // Apply search filter
        if ($filters['searchTerm']) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['searchTerm'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['searchTerm'] . '%');
            });
        }

        $pointsService = app(PointsCalculationService::class);

        return $query->get()->map(function ($user) use ($pointsService) {
            $attempts = $user->quizAttempts;
            $completed = $attempts->where('status', QuizAttempt::STATUS_COMPLETED);
            
            // Get team points breakdown using service
            $teamPointsBreakdown = $pointsService->getUserPointsBreakdown($user, $attempts);
            
            return [
                'name' => $user->name,
                'email' => $user->email,
                'team' => $user->team ? $user->team->name : 'No Team',
                'role' => $user->role,
                'total_attempts' => $attempts->count(),
                'completed_attempts' => $completed->count(),
                'completion_rate' => $attempts->count() > 0 ? round(($completed->count() / $attempts->count()) * 100, 1) : 0,
                'total_points' => $teamPointsBreakdown['total'],
                'team_points_breakdown' => $teamPointsBreakdown,
                'average_score' => $completed->count() > 0 ? round($pointsService->calculateAveragePoints($completed), 1) : 0,
                'questionnaires_completed' => $teamPointsBreakdown['questionnaires_completed'],
                'last_activity' => $attempts->max('created_at'),
                'created_at' => $user->created_at,
            ];
        })->sortByDesc('total_points')->values();
    }

    private function calculateSummaryStats($users)
    {
        $totalUsers = $users->count();
        $totalAttempts = $users->sum('total_attempts');
        $completedAttempts = $users->sum('completed_attempts');
        $averagePoints = $users->count() > 0 ? round($users->avg('total_points'), 1) : 0;

        return [
            'totalUsers' => $totalUsers,
            'totalAttempts' => $totalAttempts,
            'completedAttempts' => $completedAttempts,
            'averagePoints' => $averagePoints,
            'completionRate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 1) : 0,
        ];
    }

    private function getAnalyticsData($users, $filters)
    {
        $totalUsers = $users->count();
        
        if ($totalUsers === 0) {
            return [
                'performanceDistribution' => [],
                'topPerformers' => [],
                'completionTrends' => [],
                'teamComparison' => [],
                'engagementMetrics' => []
            ];
        }

        // Performance Distribution Analysis
        $performanceDistribution = [
            'high_performers' => $users->where('completion_rate', '>=', 80)->count(),
            'medium_performers' => $users->filter(function($user) { return $user['completion_rate'] >= 50 && $user['completion_rate'] < 80; })->count(),
            'low_performers' => $users->where('completion_rate', '<', 50)->count(),
            'inactive_users' => $users->where('total_attempts', 0)->count()
        ];

        // Top Performers (Top 5)
        $topPerformers = $users->sortByDesc('total_points')->take(5)->map(function($user) {
            return [
                'name' => $user['name'],
                'team' => $user['team'],
                'points' => $user['total_points'],
                'completion_rate' => $user['completion_rate']
            ];
        })->values();

        // Team Comparison (if multiple teams)
        $teamComparison = [];
        $teamStats = $users->groupBy('team');
        if ($teamStats->count() > 1) {
            foreach ($teamStats as $teamName => $teamUsers) {
                $teamComparison[] = [
                    'team' => $teamName,
                    'users' => $teamUsers->count(),
                    'avg_points' => round($teamUsers->avg('total_points'), 1),
                    'avg_completion' => round($teamUsers->avg('completion_rate'), 1),
                    'total_attempts' => $teamUsers->sum('total_attempts')
                ];
            }
            // Sort by average points descending
            $teamComparison = collect($teamComparison)->sortByDesc('avg_points')->values()->toArray();
        }

        // Engagement Metrics
        $engagementMetrics = [
            'average_attempts_per_user' => $totalUsers > 0 ? round($users->avg('total_attempts'), 1) : 0,
            'most_active_user' => $users->sortByDesc('total_attempts')->first()['name'] ?? 'N/A',
            'highest_completion_rate' => $users->max('completion_rate') ?? 0,
            'points_range' => [
                'highest' => $users->max('total_points') ?? 0,
                'lowest' => $users->min('total_points') ?? 0,
                'median' => $this->calculateMedian($users->pluck('total_points')->toArray())
            ]
        ];

        // Activity Timeline (simplified for timeframe)
        $completionTrends = [
            'timeframe' => $filters['selectedTimeframe'] . ' days',
            'active_users' => $users->where('last_activity', '!=', null)->count(),
            'recent_completions' => $users->sum('completed_attempts'),
            'completion_velocity' => $totalUsers > 0 ? round($users->sum('completed_attempts') / max($filters['selectedTimeframe'], 1), 2) : 0
        ];

        return [
            'performanceDistribution' => $performanceDistribution,
            'topPerformers' => $topPerformers,
            'completionTrends' => $completionTrends,
            'teamComparison' => $teamComparison,
            'engagementMetrics' => $engagementMetrics
        ];
    }

    private function calculateMedian($numbers)
    {
        if (empty($numbers) || !is_array($numbers)) return 0;
        
        // Remove non-numeric values and convert to float
        $numbers = array_filter($numbers, 'is_numeric');
        $numbers = array_map('floatval', $numbers);
        
        if (empty($numbers)) return 0;
        
        sort($numbers);
        $count = count($numbers);
        $middle = floor(($count - 1) / 2);
        
        if ($count % 2) {
            return round($numbers[$middle], 1);
        } else {
            return round(($numbers[$middle] + $numbers[$middle + 1]) / 2, 1);
        }
    }
}