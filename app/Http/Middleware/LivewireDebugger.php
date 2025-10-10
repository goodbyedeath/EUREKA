<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireDebugger
{
    public function handle(Request $request, Closure $next)
    {
        // Only debug Livewire requests
        if ($request->is('livewire/*')) {
            Log::info('=== LIVEWIRE REQUEST DEBUG ===', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
                'input' => $request->all(),
                'session_data' => [
                    'session_id' => session()->getId(),
                    'csrf_token' => csrf_token(),
                ],
                'memory_usage' => memory_get_usage(true),
                'timestamp' => now()->toISOString()
            ]);
        }

        try {
            $response = $next($request);
            
            // Debug successful responses
            if ($request->is('livewire/*')) {
                Log::info('=== LIVEWIRE RESPONSE DEBUG ===', [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                    'memory_usage_after' => memory_get_usage(true),
                    'response_size' => strlen($response->getContent()),
                    'timestamp' => now()->toISOString()
                ]);
            }

            return $response;
            
        } catch (\Exception $e) {
            // Debug failed requests
            Log::error('=== LIVEWIRE ERROR DEBUG ===', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString()
            ]);
            
            throw $e;
        }
    }
}