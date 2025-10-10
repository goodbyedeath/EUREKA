<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Check if response supports header chaining (not for StreamedResponse)
        if (method_exists($response, 'header') && !($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse)) {
            // Prevent caching
            $response->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

            // Add security headers
            $response->header('X-Content-Type-Options', 'nosniff')
                    ->header('X-Frame-Options', 'DENY')
                    ->header('X-XSS-Protection', '1; mode=block')
                    ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        } else {
            // For StreamedResponse and other response types, set headers directly
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}