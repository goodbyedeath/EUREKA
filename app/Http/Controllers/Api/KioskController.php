<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\UserQuestCheckpoint;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    /**
     * Get leaderboard data for kiosk display
     */
    public function leaderboard()
    {
        try {
            $teams = Team::select([
                'id', 
                'name', 
                'points', 
                'initial_points',
                'department',
                'created_at'
            ])
            ->with(['members:id,team_id,name,is_leader'])
            ->get()
            ->map(function ($team) {
                // Calculate detailed total score using same logic as DashboardStats
                $totalScore = $this->calculateTeamTotalScore($team);
                
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'points' => (float) $totalScore,
                    'initial_points' => (float) $team->initial_points,
                    'department' => $team->department,
                    'member_count' => $team->members->count(),
                    'leader' => $team->members->where('is_leader', true)->first()?->name,
                    'members' => $team->members->pluck('name')->toArray(),
                    'created_at' => $team->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->sortByDesc('points')
            ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'teams' => $teams,
                    'total_teams' => $teams->count(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                    'highest_score' => $teams->first()['points'] ?? 0,
                    'lowest_score' => $teams->last()['points'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaderboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team locations data for kiosk map display
     */
    public function locations()
    {
        try {
            // Get teams with their latest quest checkpoint locations
            $teams = Team::select([
                'id', 
                'name', 
                'points', 
                'department'
            ])
->with(['members' => function($query) {
                $query->select('id', 'team_id', 'name', 'is_leader');
            }])
            ->get()
            ->map(function ($team) {
                // Get the latest location data for any team member (including auto-tracking entries)
                $latestCheckpoint = UserQuestCheckpoint::whereHas('user', function($query) use ($team) {
                    $query->where('team_id', $team->id);
                })
                ->with(['questLocation:id,name,latitude,longitude', 'user:id,name'])
                ->latest('checked_at')
                ->first();

                // Debug log removed for production

                $location = null;
                if ($latestCheckpoint) {
                    // Prioritize quest location coordinates for consistency, fallback to user coordinates
                    $lat = $latestCheckpoint->questLocation?->latitude ?? $latestCheckpoint->user_latitude;
                    $lng = $latestCheckpoint->questLocation?->longitude ?? $latestCheckpoint->user_longitude;
                    
                    if ($lat && $lng) {
                        // Determine location name based on whether it's auto-tracking or quest check-in
                        $isAutoTracking = !$latestCheckpoint->quest_location_id;
                        
                        $location = [
                            'latitude' => (float) $lat,
                            'longitude' => (float) $lng,
                            'location_name' => $isAutoTracking 
                                ? 'Current Position' 
                                : ($latestCheckpoint->questLocation?->name ?? 'Quest Location'),
                            'address' => $isAutoTracking 
                                ? 'Live Tracking' 
                                : ($latestCheckpoint->questLocation?->description ?? 'Quest Location'),
                            'last_checkin' => $latestCheckpoint->checked_at->format('Y-m-d H:i:s'),
                            'last_checkin_human' => $latestCheckpoint->checked_at->diffForHumans(),
                            'checked_by' => $latestCheckpoint->user->name ?? 'Team Member',
                            'is_live_tracking' => $isAutoTracking,
                        ];
                    }
                }

                // Calculate total score using same logic as leaderboard
                $totalScore = $this->calculateTeamTotalScore($team);
                
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'points' => (float) $totalScore,
                    'department' => $team->department,
                    'member_count' => $team->members->count(),
                    'leader' => $team->members->where('is_leader', true)->first()?->name,
                    'location' => $location,
                ];
            })
            ->values();

            // If no teams have location data, show a message in logs
            $teamsWithLocations = $teams->filter(function ($team) {
                return $team['location'] !== null;
            });

            // Log removed for production

            $teams = $teamsWithLocations->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'teams' => $teams,
                    'total_teams_with_location' => $teams->count(),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team locations data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get combined data for kiosk (both leaderboard and locations)
     */
    public function data()
    {
        try {
            $leaderboardResponse = $this->leaderboard();
            $locationsResponse = $this->locations();

            if (!$leaderboardResponse->getData()->success || !$locationsResponse->getData()->success) {
                throw new \Exception('Failed to fetch kiosk data');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'leaderboard' => $leaderboardResponse->getData()->data,
                    'locations' => $locationsResponse->getData()->data,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kiosk data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate team total score: Base Points + Bonus Points from ALL team members
     * Logic: Single base points + accumulated bonus points from all team member completions
     */
    private function calculateTeamTotalScore($team)
    {
        // Single base points for the team (not multiplied by member count)
        $basePoints = $team->initial_points ?? 1000;
        $totalBonusPoints = 0;

        // Get ALL team members directly from database to ensure we don't miss any
        $teamMembers = User::where('team_id', $team->id)->get();
        
        foreach ($teamMembers as $user) {
            // Get bonus points from this user's completed questionnaires
            $userBonusPoints = $this->calculateUserBonusPoints($user, $basePoints);
            $totalBonusPoints += $userBonusPoints;
        }
        
        // Team Total = Base Points + All Bonus Points from team members
        return $basePoints + $totalBonusPoints;
    }
    
    /**
     * Calculate bonus points for individual user from completed questionnaires
     */
    private function calculateUserBonusPoints($user, $basePoints)
    {
        $bonusPoints = 0;
        
        // Get all gained points from correct answers
        $userAnswers = UserAnswer::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->with(['question'])->where('is_correct', true)->get();
        
        foreach ($userAnswers as $answer) {
            if ($answer->question) {
                $bonusPoints += $answer->question->points ?? 0;
            }
        }
        
        // Add assessment gains (bonus from fun games)
        $assessmentGains = GameAssessment::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->where('is_assessed', true)->get();
        
        foreach ($assessmentGains as $assessment) {
            // Assessment gain = total_deposit - base_points_used
            $assessmentGain = ($assessment->total_deposit ?? 0) - $basePoints;
            $bonusPoints += max(0, $assessmentGain); // Only positive gains
        }
        
        return $bonusPoints;
    }
}