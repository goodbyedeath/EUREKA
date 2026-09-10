<?php

namespace App\Http\Controllers;

use App\Models\QuestLocation;
use App\Models\UserQuestCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DebugController extends Controller
{
    public function questSystemHealth()
    {
        try {
            $health = [
                'timestamp' => now()->toISOString(),
                'user_info' => [
                    'authenticated' => Auth::check(),
                    'user_id' => Auth::id(),
                    // `getRoleNames()` comes from spatie/laravel-permission, which this app
                    // does not use — the call threw and took the whole health check to a
                    // 500. Roles here are a single column on the user.
                    'user_role' => Auth::user()?->role,
                ],
                'database' => [
                    'connection' => 'Unknown',
                    'quest_locations_count' => 0,
                    'active_locations_count' => 0,
                    'user_checkpoints_count' => 0,
                ],
                'dependencies' => [
                    'laravel_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                    'memory_usage' => memory_get_usage(true),
                    'memory_limit' => ini_get('memory_limit'),
                ],
                'livewire' => [
                    'available' => class_exists(\Livewire\Component::class),
                    'version' => 'Unknown',
                ],
                'files' => [],
                'logs' => []
            ];

            // Test database connection
            try {
                DB::connection()->getPdo();
                $health['database']['connection'] = 'Connected';
                
                // Get counts
                $health['database']['quest_locations_count'] = QuestLocation::count();
                $health['database']['active_locations_count'] = QuestLocation::active()->count();
                $health['database']['user_checkpoints_count'] = UserQuestCheckpoint::count();
                
            } catch (\Exception $e) {
                $health['database']['connection'] = 'Failed: ' . $e->getMessage();
            }

            // Check critical files.
            //
            // Three entries were dropped: Livewire/User/QuestLocationDashboard.php,
            // views/livewire/user/quest-location-dashboard.blade.php and
            // js/quest-location-dashboard-simple.js. None of them exist any more, so the
            // check reported them missing on every run — a health report that is always
            // red teaches people to ignore it, which is worse than not having one.
            $criticalFiles = [
                'QuestLocationManager.php' => app_path('Livewire/Admin/QuestLocationManager.php'),
                'QuestLocation.php' => app_path('Models/QuestLocation.php'),
                'UserQuestCheckpoint.php' => app_path('Models/UserQuestCheckpoint.php'),
                'quest-location-manager.blade.php' => resource_path('views/livewire/admin/quest-location-manager.blade.php'),
                'quest-location-dashboard.blade.php' => resource_path('views/user/quest-location-dashboard.blade.php'),
            ];

            foreach ($criticalFiles as $name => $path) {
                $health['files'][$name] = [
                    'exists' => file_exists($path),
                    'readable' => file_exists($path) && is_readable($path),
                    'size' => file_exists($path) ? filesize($path) : 0,
                    'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                ];
            }

            // Get recent logs
            try {
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    $logs = file($logFile);
                    $health['logs']['recent_entries'] = array_slice($logs, -10);
                    $health['logs']['total_lines'] = count($logs);
                } else {
                    $health['logs']['recent_entries'] = ['Log file not found'];
                }
            } catch (\Exception $e) {
                $health['logs']['error'] = $e->getMessage();
            }

            // Check Livewire version if available
            if (class_exists(\Livewire\Component::class)) {
                try {
                    if (defined('\Livewire\Livewire::VERSION')) {
                        $health['livewire']['version'] = \Livewire\Livewire::VERSION;
                    }
                } catch (\Exception $e) {
                    $health['livewire']['version_error'] = $e->getMessage();
                }
            }

            return response()->json($health, 200);

        } catch (\Exception $e) {
            Log::error('Debug health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Health check failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    public function testDatabase()
    {
        try {
            $results = [
                'timestamp' => now()->toISOString(),
                'tests' => []
            ];

            // Test 1: Basic connection
            try {
                DB::connection()->getPdo();
                $results['tests']['database_connection'] = 'SUCCESS';
            } catch (\Exception $e) {
                $results['tests']['database_connection'] = 'FAILED: ' . $e->getMessage();
            }

            // Test 2: QuestLocation model
            try {
                $location = QuestLocation::first();
                $results['tests']['quest_location_model'] = $location ? 'SUCCESS - Found data' : 'SUCCESS - No data';
                $results['sample_location'] = $location ? $location->toArray() : null;
            } catch (\Exception $e) {
                $results['tests']['quest_location_model'] = 'FAILED: ' . $e->getMessage();
            }

            // Test 3: UserQuestCheckpoint model
            try {
                $checkpoint = UserQuestCheckpoint::first();
                $results['tests']['user_checkpoint_model'] = $checkpoint ? 'SUCCESS - Found data' : 'SUCCESS - No data';
                $results['sample_checkpoint'] = $checkpoint ? $checkpoint->toArray() : null;
            } catch (\Exception $e) {
                $results['tests']['user_checkpoint_model'] = 'FAILED: ' . $e->getMessage();
            }

            // Removed: a check that instantiated \App\Livewire\User\QuestLocationDashboard,
            // a class that does not exist. It sat inside a catch, so the report cheerfully
            // printed "FAILED: Class not found" every time and nobody noticed — a health
            // check that always fails tells you nothing about health.

            return response()->json($results, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database test failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    public function clearLogs()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                return response()->json(['message' => 'Logs cleared successfully']);
            } else {
                return response()->json(['message' => 'Log file not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to clear logs: ' . $e->getMessage()], 500);
        }
    }
}