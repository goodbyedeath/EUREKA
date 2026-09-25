<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\IndoorMapSpot;
use App\Models\GameLocationUnlock;
use App\Models\RaceSession;
use App\Models\IndoorMap;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamMember;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use App\Models\GameAssessment;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\TeamScoreAdjustment;
use App\Services\PointsCalculationService;

class TeamManager extends Component
{
    use WithPagination;

    public $searchTerm = '';
    
    // Team form data
    public $teamId = null;
    public $name = '';
    public $description = '';
    public $department = '';
    public $points = 0;
    public $initial_points = 0;
    
    /**
     * Amounts the crew reaches for most often, as buttons that only fill the box — the reason is
     * still typed, because a correction whose reason reads "Quick bonus" explains nothing three hours
     * later when a team asks why its score moved.
     */
    public array $quickAmounts = [25, 50, 100, 200, 500];

    /** The team whose manual corrections are open, and the row being typed. */
    public ?int $adjustTeamId = null;
    /** Set while "clear every correction" is waiting for a second click. */
    public ?int $confirmClearTeamId = null;
    /** Set while "stop the clock" is waiting for a second click. */
    public ?int $confirmStopTeamId = null;
    public $adjustAmount = 0;
    public string $adjustReason = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'department' => 'nullable|string|max:255',
        'points' => 'required|integer|min:0|max:999999',
        'initial_points' => 'required|integer|min:0|max:999999',
    ];

    protected $messages = [
        'name.required' => 'Team name is required.',
        'name.max' => 'Team name cannot exceed 255 characters.',
        'points.required' => 'Points are required.',
        'points.integer' => 'Points must be a whole number.',
        'points.min' => 'Points cannot be negative.',
        'initial_points.required' => 'Initial points are required.',
        'initial_points.integer' => 'Initial points must be a whole number.',
        'initial_points.min' => 'Initial points cannot be negative.',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function createTeam()
    {
        $this->resetForm();
        $this->dispatch('open-team-modal', [
            'teamId' => null,
            'name' => '',
            'department' => '',
            'description' => '',
            'initial_points' => 0,
            'points' => 0
        ]);
    }

    public function editTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            $this->dispatch('open-team-modal', [
                'teamId' => $team->id,
                'name' => $team->name,
                'department' => $team->department ?? '',
                'description' => $team->description ?? '',
                'initial_points' => $team->initial_points,
                'points' => $team->points
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Team not found or could not be loaded.');
        }
    }

    public function saveTeam($teamData)
    {
        // Validate the incoming data
        $validator = validator($teamData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'department' => 'nullable|string|max:255',
            'points' => 'required|integer|min:0|max:999999',
            'initial_points' => 'required|integer|min:0|max:999999',
        ]);
        
        if ($validator->fails()) {
            session()->flash('error', 'Validation failed: ' . $validator->errors()->first());
            return;
        }

        if ($teamData['teamId']) {
            // Update existing team
            $team = Team::findOrFail($teamData['teamId']);
            $team->update([
                'name' => $teamData['name'],
                'description' => $teamData['description'],
                'department' => $teamData['department'],
                'points' => $teamData['points'],
                'initial_points' => $teamData['initial_points'],
            ]);
            
            session()->flash('success', 'Team updated successfully!');
        } else {
            // Create new team (admin created teams don't automatically have members)
            $team = Team::create([
                'name' => $teamData['name'],
                'description' => $teamData['description'],
                'department' => $teamData['department'],
                'points' => $teamData['points'],
                'initial_points' => $teamData['initial_points'],
                'created_by' => auth()->id(),
            ]);
            
            session()->flash('success', 'Team created successfully! You can now add members to this team.');
        }

        $this->dispatch('close-team-modal');
        $this->resetForm();
    }

    /**
     * Remove one team and put its accounts back to new.
     *
     * The surgical tool between the two blunt ones: Arsipkan ends the whole session, Emergency Stop
     * wipes every team's run. This is for one team that has to start over — a botched setup in the
     * app, the wrong people on the wrong account.
     *
     * It used to delete only the attempts, and its own confirmation promised the accounts were "reset
     * to NEW" (operator, 23 Sep). They were not: the race clock, the posts the crew had opened, the
     * scans and the GPS trail all stayed, so a team that re-registered with the same login card woke
     * up inside its old race — sometimes already finished, with half its posts shut. It now clears
     * the same per-account trail an emergency stop does, for these accounts only.
     *
     * Kept on purpose: the accounts themselves and their login cards, because the point is to let
     * the same people start again; and the authored content, which is never a team's to delete.
     */
    public function forceDeleteTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $removed = [];

            DB::transaction(function () use ($team, &$removed) {
                $userIds = User::where('team_id', $team->id)->pluck('id');

                if ($userIds->count() > 0) {
                    // Photographs live on disk, so collect their paths before the rows go.
                    $removed['photos'] = GameAssessment::whereIn('user_id', $userIds)
                        ->pluck('facilitator_photo')
                        ->filter(fn ($path) => str_starts_with((string) $path, 'facilitator-photos/'))
                        ->values()->all();

                    User::whereIn('id', $userIds)->update(['team_id' => null]);

                    UserAnswer::whereHas('quizAttempt', function ($query) use ($userIds) {
                        $query->whereIn('user_id', $userIds);
                    })->delete();

                    GameAssessment::whereHas('quizAttempt', function ($query) use ($userIds) {
                        $query->whereIn('user_id', $userIds);
                    })->delete();

                    $removed['attempts'] = QuizAttempt::whereIn('user_id', $userIds)->delete();

                    // The rest of the trail. Without these the account is not new: the clock keeps
                    // running, and a post the crew opened — or one closed because it was finished —
                    // stays as it was.
                    $removed['races'] = RaceSession::whereIn('user_id', $userIds)->delete();
                    $removed['scans'] = \App\Models\QrCodeScan::whereIn('user_id', $userIds)->delete();
                    $removed['checkins'] = \App\Models\UserQuestCheckpoint::whereIn('user_id', $userIds)->delete();
                    $removed['unlocks'] = GameLocationUnlock::whereIn('user_id', $userIds)->delete();
                    $removed['claims'] = \App\Models\HotspotClaim::whereIn('user_id', $userIds)->delete();
                    DB::table('gps_footprints')->whereIn('user_id', $userIds)->delete();
                    DB::table('gps_tracking_sessions')->whereIn('user_id', $userIds)->delete();
                }

                $team->members()->delete();
                $team->delete();
            });

            // Files are not transactional: touch them only once the rows are gone for good.
            if (! empty($removed['photos'])) {
                $disk->delete($removed['photos']);
            }

            Log::info('Team force deleted', ['team_id' => $teamId] + array_map(
                fn ($v) => is_array($v) ? count($v) : $v, $removed
            ));

            session()->flash('success', sprintf(
                'Tim dihapus. Akunnya dilepas dan kembali kosong: %d attempt, %d jam race, %d scan, %d pos terbuka dan jejak GPS-nya dihapus. Kartu loginnya tetap berlaku.',
                $removed['attempts'] ?? 0, $removed['races'] ?? 0, $removed['scans'] ?? 0, $removed['unlocks'] ?? 0,
            ));
        } catch (\Exception $e) {
            Log::error('Force team deletion error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to delete team: ' . $e->getMessage());
        }
    }

    public function removeMemberFromTeam($teamId, $memberId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $member = TeamMember::where('team_id', $teamId)->findOrFail($memberId);
            
            // Check if this is the last leader
            if ($member->is_leader) {
                $leaderCount = $team->members()->where('is_leader', true)->count();
                if ($leaderCount <= 1 && $team->members()->count() > 1) {
                    session()->flash('error', 'Cannot remove the last team leader. Please assign leadership to another member first.');
                    return;
                }
            }
            
            DB::transaction(function () use ($member) {
                // If this member is linked to a user, remove the team association
                if ($member->user_id) {
                    $user = User::find($member->user_id);
                    if ($user) {
                        $user->team_id = null;
                        $user->save();
                    }
                }
                
                // Delete the team member record
                $member->delete();
            });
            
            session()->flash('success', 'Team member removed successfully!');
        } catch (\Exception $e) {
            Log::error('Remove member error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'member_id' => $memberId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to remove team member: ' . $e->getMessage());
        }
    }

    public function removeUserFromTeam($teamId, $userId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $user = User::where('team_id', $teamId)->findOrFail($userId);
            
            DB::transaction(function () use ($user) {
                // Remove user from team
                $user->team_id = null;
                $user->save();
                
                // Also remove from team_members if they exist there
                TeamMember::where('user_id', $user->id)->delete();
            });
            
            session()->flash('success', 'User removed from team successfully!');
        } catch (\Exception $e) {
            Log::error('Remove user error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'user_id' => $userId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to remove user from team: ' . $e->getMessage());
        }
    }

    public function deleteTeam($teamId)
    {
        try {
            $team = Team::findOrFail($teamId);
            
            // Check if team has users (via team_id foreign key)
            $usersInTeam = User::where('team_id', $team->id)->count();
            if ($usersInTeam > 0) {
                session()->flash('error', 'Cannot delete team with existing users. Please remove all users from this team first.');
                return;
            }
            
            // Check if team has members in team_members table
            $membersCount = $team->members()->count();
            if ($membersCount > 0) {
                session()->flash('error', 'Cannot delete team with existing members. Please remove all members first.');
                return;
            }
            
            // Use database transaction for safe deletion
            DB::transaction(function () use ($team) {
                // For empty teams, we still need to clean up any orphaned data
                // that might be referenced by team_id
                
                // Delete team members (should be empty but just in case)
                $team->members()->delete();
                
                // Finally delete the team
                $team->delete();
            });
            
            session()->flash('success', 'Team deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Team deletion error: ' . $e->getMessage(), [
                'team_id' => $teamId,
                'error' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Failed to delete team: ' . $e->getMessage());
        }
    }

    /**
     * Correct a team's score by hand.
     *
     * The crew asked for this on 25 Sep for the case the app cannot cover by itself: a facilitator's
     * phone dies before a game is scored, a booth's QR never printed, a questionnaire was handed in
     * twice. Nothing is overwritten — a signed row is added to team_score_adjustments with a reason
     * and a name, PointsCalculationService sums it, and the kiosk, /admin/user-progress and the team
     * card in the app all move together.
     */
    public function openAdjust(int $teamId): void
    {
        $this->adjustTeamId = $teamId;
        $this->adjustAmount = 0;
        $this->adjustReason = '';
        $this->resetValidation();
    }

    public function closeAdjust(): void
    {
        $this->adjustTeamId = null;
        $this->confirmClearTeamId = null;
    }

    public function askClear(int $teamId): void
    {
        $this->confirmClearTeamId = $teamId;
    }

    public function cancelClear(): void
    {
        $this->confirmClearTeamId = null;
    }

    /** $sign is +1 to award, −1 to take away. */
    public function saveAdjust(int $sign): void
    {
        $this->validate([
            // Zero would be a row that says nothing; the reason is the whole point of the row.
            'adjustAmount' => 'required|integer|min:1|max:999999',
            'adjustReason' => 'required|string|min:3|max:255',
        ], [
            'adjustAmount.required' => 'Isi jumlah poinnya.',
            'adjustAmount.min' => 'Jumlahnya minimal 1.',
            'adjustReason.required' => 'Tulis alasannya — baris ini yang akan dibaca saat hasil dipertanyakan.',
            'adjustReason.min' => 'Alasannya terlalu pendek.',
        ]);

        $team = Team::findOrFail($this->adjustTeamId);
        $points = ($sign < 0 ? -1 : 1) * (int) $this->adjustAmount;

        TeamScoreAdjustment::create([
            'team_id' => $team->id,
            'points' => $points,
            'reason' => trim($this->adjustReason),
            'created_by' => Auth::id(),
        ]);

        Log::info('Team score corrected by hand', [
            'team_id' => $team->id, 'points' => $points,
            'reason' => trim($this->adjustReason), 'by' => Auth::id(),
        ]);

        $this->adjustAmount = 0;
        $this->adjustReason = '';

        session()->flash('success', ($points > 0 ? '+' : '').$points." poin untuk {$team->name}.");
    }

    /** Undo one correction. The row is removed, so the score goes back to what it was. */
    public function undoAdjust(int $id): void
    {
        $row = TeamScoreAdjustment::find($id);
        if (! $row) {
            return;
        }

        $points = $row->points;
        $row->delete();

        Log::info('Manual score correction removed', ['adjustment_id' => $id, 'points' => $points, 'by' => Auth::id()]);
        session()->flash('success', 'Koreksi '.($points > 0 ? '+' : '').$points.' poin dibatalkan.');
    }

    /**
     * The race clock, stopped by hand for one team.
     *
     * The clock is the one the team started by scanning its START QR, and until now only two things
     * could stop it: clearing every counting post, or the code's time limit running out. The crew asked
     * for a third (25 Sep) — a team that gave up, one pulled out of the venue, a phone that died with
     * the race still running. `finished_at` is the moment the crew said stop, and that becomes the
     * team's official time; new posts are then refused like any finished run, while a quiz already open
     * can still be handed in.
     */
    public function askStopTimer(int $teamId): void
    {
        $this->confirmStopTeamId = $teamId;
    }

    public function cancelStopTimer(): void
    {
        $this->confirmStopTeamId = null;
    }

    public function stopTimer(int $teamId): void
    {
        $this->confirmStopTeamId = null;
        $team = Team::findOrFail($teamId);
        $session = $this->runningSessionFor($team);

        if (! $session) {
            session()->flash('error', "{$team->name} tidak punya jam race yang sedang berjalan.");

            return;
        }

        $stopped = $session->stopByCrew(Auth::id());
        session()->flash('success', "Jam race {$team->name} dihentikan pada {$session->elapsedForHumans()}"
            .($stopped > 1 ? " ({$stopped} sesi)" : '').'.');
    }

    /** Undo a stop the crew made; a clock that ran out or a finished route is not reopened here. */
    public function resumeTimer(int $teamId): void
    {
        $team = Team::findOrFail($teamId);
        $session = RaceSession::whereIn('user_id', User::where('team_id', $team->id)->pluck('id'))
            ->where('finished_reason', RaceSession::REASON_MANUAL)
            ->orderByDesc('started_at')->orderByDesc('id')->first();

        if (! $session) {
            session()->flash('error', "{$team->name} tidak punya jam yang dihentikan kru.");

            return;
        }

        $session->resumeAfterCrewStop(Auth::id());
        session()->flash('success', "Jam race {$team->name} berjalan lagi.");
    }

    /** The team's live run, whichever phone holds it. */
    private function runningSessionFor(Team $team): ?RaceSession
    {
        return RaceSession::whereIn('user_id', User::where('team_id', $team->id)->pluck('id'))
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->orderByDesc('started_at')->orderByDesc('id')
            ->first();
    }

    /** What to draw in the team's clock cell: the run itself, whichever phone holds it. */
    private function clockFor(Team $team): ?RaceSession
    {
        return RaceSession::with('raceStart')
            ->whereIn('user_id', User::where('team_id', $team->id)->pluck('id'))
            ->whereNotNull('started_at')
            ->orderByDesc('started_at')->orderByDesc('id')
            ->first();
    }

    /** The team the correction panel is for, or null when it is closed. */
    public function getAdjustTeamProperty()
    {
        return $this->adjustTeamId ? Team::find($this->adjustTeamId) : null;
    }

    /** The corrections of the open team, newest first. */
    public function getAdjustmentsProperty()
    {
        if (! $this->adjustTeamId) {
            return collect();
        }

        return TeamScoreAdjustment::with('author:id,name')
            ->where('team_id', $this->adjustTeamId)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get();
    }

    public function resetTeamPoints($teamId)
    {
        $team = Team::findOrFail($teamId);

        // Only the manual corrections: what the team earned in the race is the race's to give and the
        // emergency stop's to take, not this button's.
        $removed = TeamScoreAdjustment::where('team_id', $team->id)->count();
        TeamScoreAdjustment::where('team_id', $team->id)->delete();

        $this->confirmClearTeamId = null;

        session()->flash('success', $removed
            ? "{$removed} koreksi manual dihapus dari {$team->name}; poin hasil race tidak disentuh."
            : "{$team->name} tidak punya koreksi manual.");
    }


    
    public function viewMembers($teamId)
    {
        $team = Team::with(['members.user'])->findOrFail($teamId);
        
        Log::info('ViewMembers called', ['team_id' => $teamId, 'members_count' => $team->members->count()]);

        $members = $team->members->map(function($member) {
            $user = $member->user;

            // Fallback manually if user is null and we have email
            if (!$user && $member->email) {
                $user = User::where('email', $member->email)->first();
            }

            return [
                'id' => $member->id,
                'name' => $user?->name ?? $member->name ?? 'Unknown',
                'email' => $user?->email ?? $member->email ?? 'No email',
                'is_leader' => (bool)$member->is_leader,
                'joined_date' => $member->created_at?->format('M d, Y') ?? 'Unknown',
                'type' => 'member'
            ];
        });

        $modalData = [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $members->toArray()
        ];
        
        Log::info('Dispatching members modal', $modalData);
        
        $this->dispatch('open-members-modal', $modalData);
        
    }

    public function viewTeamUsers($teamId)
    {
        $team = Team::findOrFail($teamId);
        $users = User::where('team_id', $teamId)->get();
        
        $usersList = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_leader' => false, // Users don't have leader status in users table
                'joined_date' => $user->created_at?->format('M d, Y') ?? 'Unknown',
                'type' => 'user'
            ];
        });

        $modalData = [
            'id' => $team->id,
            'name' => $team->name,
            'members' => $usersList->toArray()
        ];
        
        $this->dispatch('open-users-modal', $modalData);
    }

    public function resetForm()
    {
        $this->teamId = null;
        $this->name = '';
        $this->description = '';
        $this->department = '';
        $this->points = 0;
        $this->initial_points = 0;
    }

    public function getTeamsProperty()
    {
        try {
            $query = Team::with(['members', 'creator']);

            if ($this->searchTerm) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('department', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
                });
            }

            $teams = $query->orderBy('created_at', 'desc')->paginate(10);
            
            // Calculate real total scores for each team and user counts
            foreach ($teams as $team) {
                try {
                    $team->calculated_total_score = $this->calculateTeamTotalScore($team);
                    $team->manual_score = (int) TeamScoreAdjustment::where('team_id', $team->id)->sum('points');
                    $team->race = $this->clockFor($team);
                    $team->users_count = User::where('team_id', $team->id)->count();
                } catch (\Exception $e) {
                    Log::warning('Error calculating team data for team ' . $team->id . ': ' . $e->getMessage());
                    $team->calculated_total_score = $team->initial_points ?? 0;
                    $team->users_count = 0;
                }
            }
            
            return $teams;
        } catch (\Exception $e) {
            Log::error('Error in getTeamsProperty: ' . $e->getMessage());
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1);
        }
    }
    
    /**
     * Calculate team's total score using the correct logic:
     * Base Points + Bonus Points from all team members' completed questionnaires
     */
    private function calculateTeamTotalScore($team)
    {
        // The shared formula, not a copy of it: base + answers + facilitator games + tapped objects +
        // the crew's manual corrections. The copy that used to live here counted neither the objects
        // nor the corrections, so this page showed a lower number than the kiosk beside it.
        return app(PointsCalculationService::class)->teamScore($team)['total'];
    }
    
    /**
     * Calculate bonus points for a specific user from completed questionnaires only
     * Uses the EXACT same logic as user-progress page (individual question points + assessment bonus)
     */
    private function calculateUserBonusPoints($user)
    {
        // Points actually earned, as user-progress and the leaderboard read them. This used to add
        // each correct answer's question->points, which counted a fun_game twice — its question
        // points (the answer is stored correct with 0 earned) on top of the facilitator's score
        // below — and could not express the partial credit a "Tebak Gambar" answer earns.
        $earnedPoints = (int) \App\Models\UserAnswer::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->sum('points_earned');
        
        // Add assessment gains (bonus from fun games) - same as user-progress
        $assessmentGains = \App\Models\GameAssessment::whereHas('quizAttempt', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->where('is_assessed', true)->get();
        
        $assessmentBonus = 0;
        $basePoints = $user->team ? ($user->team->initial_points ?? 0) : 0;
        foreach ($assessmentGains as $assessment) {
            // Assessment gain = total_deposit - base_points_used
            $assessmentGain = ($assessment->total_deposit ?? 0) - $basePoints;
            $assessmentBonus += max(0, $assessmentGain);
        }
        
        return $earnedPoints + $assessmentBonus;
    }

    public function viewGameAssessments($teamId)
    {
        $team = Team::findOrFail($teamId);
        
        // Get team members
        $teamMembers = User::where('team_id', $team->id)->get();
        $memberIds = $teamMembers->pluck('id');
        
        // Get game assessments with verification photos for this team
        $gameAssessments = GameAssessment::with(['quizAttempt.user', 'quizAttempt.questionnaire'])
            ->whereHas('quizAttempt', function($query) use ($memberIds) {
                $query->whereIn('user_id', $memberIds)
                      ->whereNotNull('verification_photo')
                      ->where('verification_photo', '!=', '');
            })
            ->where('is_assessed', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $assessmentsData = $gameAssessments->map(function($assessment) {
            $quizAttempt = $assessment->quizAttempt;
            return [
                'id' => $assessment->id,
                'user_name' => $quizAttempt->user->name ?? 'Unknown',
                'questionnaire_title' => $quizAttempt->questionnaire->title ?? 'Unknown Quiz',
                'total_deposit' => $assessment->total_deposit ?? 0,
                'completed_at' => $quizAttempt->completed_at?->format('M d, Y H:i'),
                'photo_captured_at' => $quizAttempt->photo_captured_at?->format('M d, Y H:i'),
                'verification_photo' => $quizAttempt->verification_photo
            ];
        });
        
        $modalData = [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'assessments' => $assessmentsData->toArray()
        ];
        
        $this->dispatch('open-assessments-modal', $modalData);
    }

    /**
     * Send a team to its own floor plan (operator, 16 Sep).
     *
     * Indoor events run several teams at once on different routes. Empty means the team falls back
     * to the plan on the START code, which is what every team did before. Teams that have already
     * scanned START keep the plan their session recorded until the race is reset — moving them
     * mid-race would swap the map under their feet.
     */
    public function setIndoorMap(int $teamId, $mapId): void
    {
        $team = Team::findOrFail($teamId);
        $map = $mapId ? IndoorMap::where('is_active', true)->find($mapId) : null;

        if ($mapId && ! $map) {
            session()->flash('error', 'Denah itu tidak ada atau sedang nonaktif.');

            return;
        }

        $team->update(['indoor_map_id' => $map?->id]);

        // Posts opened on the old plan would otherwise stay open on Outpost Access and in the AR
        // gate, for a plan this team no longer walks (operator, 20 Sep).
        $closed = $this->closeOffPlanAccess($team, $map?->id);
        $note = $closed ? " {$closed} akses pos dari denah lama ditutup." : '';

        $running = RaceSession::whereIn('user_id', User::where('team_id', $team->id)->pluck('id'))
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->exists();

        session()->flash('success', $map
            ? "Tim {$team->name} diarahkan ke denah \"{$map->name}\"."
                .$note
                .($running ? ' Tim ini sudah memulai race, jadi denah barunya berlaku setelah race direset.' : '')
            : "Tim {$team->name} kembali memakai denah dari QR START.".$note);
    }

    /**
     * Revoke this team's open posts that its new plan does not carry.
     *
     * Without this the desk kept showing posts from the plan the team has just left, and the AR
     * gate would still have let them in.
     *
     * @return int how many were closed
     */
    private function closeOffPlanAccess(Team $team, ?int $mapId): int
    {
        // No assignment means the team follows its START code again; nothing is stale by
        // definition, so only an explicit plan closes anything.
        if (! $mapId) {
            return 0;
        }

        $accounts = User::where('team_id', $team->id)->pluck('id');
        if ($accounts->isEmpty()) {
            return 0;
        }

        $onPlan = IndoorMapSpot::where('indoor_map_id', $mapId)
            ->where('is_active', true)
            ->whereNotNull('game_location_id')
            ->pluck('game_location_id')
            ->all();

        return GameLocationUnlock::whereIn('user_id', $accounts)
            ->whereNull('revoked_at')
            ->when($onPlan, fn ($q) => $q->whereNotIn('game_location_id', $onPlan))
            ->update(['revoked_at' => now()]);
    }

    public function render()
    {
        $teams = $this->getTeamsProperty();

        return view('livewire.admin.team-manager', [
            'teams' => $teams,
            'indoorMaps' => IndoorMap::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ])->layout(null);
    }
}