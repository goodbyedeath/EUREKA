<?php

namespace App\Console\Commands;

use App\Models\FeatureSetting;
use App\Services\WorkflowTimerService;
use Illuminate\Console\Command;

class ManageWorkflowTimers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow-timers:manage {action : Action to perform (enable|disable|status|stats)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage workflow timer features for gradual rollout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'enable':
                $this->enableWorkflowTimers();
                break;
            case 'disable':
                $this->disableWorkflowTimers();
                break;
            case 'status':
                $this->showStatus();
                break;
            case 'stats':
                $this->showStatistics();
                break;
            default:
                $this->error("Invalid action. Use: enable, disable, status, or stats");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function enableWorkflowTimers()
    {
        try {
            $feature = FeatureSetting::where('feature_key', 'workflow_timers')->first();
            
            if (!$feature) {
                $this->error('Workflow timers feature not found. Please run database seeders first.');
                return;
            }

            if ($feature->is_enabled) {
                $this->info('Workflow timers are already enabled.');
                return;
            }

            $feature->update(['is_enabled' => true]);
            FeatureSetting::clearCache();

            $this->info('✅ Workflow timers have been enabled!');
            $this->line('   - Server-side session and quiz timers are now active');
            $this->line('   - Users will benefit from more reliable timing');
            $this->line('   - Client-side timers will be used as fallback');
            
        } catch (\Exception $e) {
            $this->error('Failed to enable workflow timers: ' . $e->getMessage());
        }
    }

    private function disableWorkflowTimers()
    {
        try {
            $feature = FeatureSetting::where('feature_key', 'workflow_timers')->first();
            
            if (!$feature) {
                $this->error('Workflow timers feature not found.');
                return;
            }

            if (!$feature->is_enabled) {
                $this->info('Workflow timers are already disabled.');
                return;
            }

            $feature->update(['is_enabled' => false]);
            FeatureSetting::clearCache();

            $this->info('⚠️  Workflow timers have been disabled.');
            $this->line('   - Falling back to client-side timer system');
            $this->line('   - Users will continue to experience normal functionality');
            
        } catch (\Exception $e) {
            $this->error('Failed to disable workflow timers: ' . $e->getMessage());
        }
    }

    private function showStatus()
    {
        try {
            $feature = FeatureSetting::where('feature_key', 'workflow_timers')->first();
            
            if (!$feature) {
                $this->error('Workflow timers feature not found.');
                return;
            }

            $status = $feature->is_enabled ? 'ENABLED' : 'DISABLED';
            $color = $feature->is_enabled ? 'green' : 'yellow';

            $this->line("Workflow Timers Status: <fg={$color}>{$status}</>");
            $this->line("Description: {$feature->description}");
            
            if ($feature->is_enabled) {
                $this->line("✅ Server-side workflow timers are active");
            } else {
                $this->line("⚠️  Using client-side timer fallback");
            }
            
        } catch (\Exception $e) {
            $this->error('Failed to get workflow timer status: ' . $e->getMessage());
        }
    }

    private function showStatistics()
    {
        try {
            $timerService = app(WorkflowTimerService::class);
            $stats = $timerService->getTimerStatistics();

            $this->line('<fg=blue>Workflow Timer Statistics:</>');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            $this->line("Feature Status: " . ($stats['feature_enabled'] ? '<fg=green>ENABLED</>' : '<fg=yellow>DISABLED</>'));
            $this->line("Active Session Timers: <fg=cyan>{$stats['active_session_timers']}</>");
            $this->line("Active Quiz Timers: <fg=cyan>{$stats['active_quiz_timers']}</>");
            $this->line("Total Running Workflows: <fg=cyan>{$stats['total_workflows']}</>");
            
            if ($stats['feature_enabled'] && ($stats['active_session_timers'] > 0 || $stats['active_quiz_timers'] > 0)) {
                $this->line('<fg=green>✅ Workflow timers are actively managing user sessions</fg=green>');
            } elseif (!$stats['feature_enabled']) {
                $this->line('<fg=yellow>⚠️  Workflow timers disabled - using client-side fallback</fg=yellow>');
            } else {
                $this->line('<fg=blue>ℹ️  Workflow timers enabled but no active sessions</fg=blue>');
            }
            
        } catch (\Exception $e) {
            $this->error('Failed to get workflow timer statistics: ' . $e->getMessage());
        }
    }
}