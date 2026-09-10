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
            // The race clock, newest run per account. Indoor events start it at the
            // START scan; an outdoor event simply has none, and the field stays null.
            //
            // Ordered ASCENDING on purpose: keyBy() lets a later row overwrite an earlier
            // one with the same key, so the *last* session read is the one kept. Sorting
            // descending — which reads as "newest first" — therefore keeps the OLDEST run,
            // and a team that restarted an aborted run would show a stale, already-finished
            // clock on the big screen for the rest of the event.
            $races = \App\Models\RaceSession::whereNotNull('started_at')
                ->orderBy('started_at')
                ->orderBy('id')
                ->get()
                ->keyBy('user_id');

            // Indoor 'position': GPS cannot see through a roof, so the outpost the crew
            // has opened for a team is what tells the big screen where that team is.
            $unlocks = \App\Models\GameLocationUnlock::whereNull('revoked_at')
                ->whereNotNull('granted_at')
                ->with('gameLocation:id,name')
                ->get()
                ->groupBy('user_id');

            $teams = Team::select([
                'id', 
                'name', 
                'points', 
                'initial_points',
                'department',
                'created_at'
            ])
            // users = the team's login accounts; race sessions and outpost unlocks both
            // hang off those, and indoors they are the only signal of where a team is.
            ->with(['members:id,team_id,name,is_leader', 'users:id,team_id,name'])
            ->get()
            ->map(function ($team) use ($races, $unlocks) {
                // Use the actual points column from database (updated in real-time when quizzes are submitted)
                $totalScore = $team->points;

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

                    // --- added 2026-09-07, so the big screen reflects the indoor build ---

                    // Elapsed run time. Read from the server's start instant rather than
                    // counted on the screen, so a kiosk that reloads shows the same number.
                    'race' => (function () use ($team, $races) {
                        foreach ($team->users as $u) {
                            if ($r = $races->get($u->id)) {
                                return [
                                    'started_at' => $r->started_at->toIso8601String(),
                                    'elapsed_seconds' => $r->elapsedSeconds(),
                                    'elapsed' => $r->elapsedForHumans(),
                                    'finished' => $r->finished_at !== null,
                                ];
                            }
                        }
                        return null;                      // outdoor, or not started yet
                    })(),

                    // Which outposts the crew currently has open for this team.
                    'at_outposts' => (function () use ($team, $unlocks) {
                        $out = [];
                        foreach ($team->users as $u) {
                            foreach ($unlocks->get($u->id, collect()) as $row) {
                                if ($row->gameLocation) {
                                    $out[] = ['id' => $row->gameLocation->id, 'name' => $row->gameLocation->name];
                                }
                            }
                        }
                        return $out;
                    })(),
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

                // Use the actual points column from database (updated in real-time when quizzes are submitted)
                $totalScore = $team->points;

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

            // Live positions ride along here rather than being a second call.
            //
            // The LED board polled /api/kiosk/data and /api/live/positions separately, on
            // two different timers, for one screen — ten requests a minute where six will
            // do. Both read the same database within milliseconds of each other, so there
            // was never a reason to ask twice.
            $positions = app(LiveTrackingController::class)->getCurrentPositions();

            return response()->json([
                'success' => true,
                'data' => [
                    'leaderboard' => $leaderboardResponse->getData()->data,
                    'locations' => $locationsResponse->getData()->data,
                    'live_positions' => $positions->getData()->data ?? null,
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
            $bonusPoints += $assessmentGain; // Penalties count too, so this can be negative
        }
        
        return $bonusPoints;
    }
}