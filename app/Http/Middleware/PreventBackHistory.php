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
                    ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
                    ->header('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

            // Add Content Security Policy (permissive for development, tighten in production)
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://maps.googleapis.com",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
                "img-src 'self' data: https: blob:",
                "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "connect-src 'self' https://api.maptiler.com https://maps.googleapis.com",
                "frame-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "upgrade-insecure-requests"
            ]);
            $response->header('Content-Security-Policy', $csp);
        } else {
            // For StreamedResponse and other response types, set headers directly
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

            // Add Content Security Policy
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://maps.googleapis.com",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
                "img-src 'self' data: https: blob:",
                "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "connect-src 'self' https://api.maptiler.com https://maps.googleapis.com",
                "frame-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "upgrade-insecure-requests"
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}