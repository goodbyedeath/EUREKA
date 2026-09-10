<?php


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register global middleware
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\TrustCloudflareProxies::class, // Trust Cloudflare proxy IPs
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            // Legacy middleware (still functional)
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            
            // New optimized middleware
            'role' => \App\Http\Middleware\RoleBasedAccessMiddleware::class,
            'preventbackhistory' => \App\Http\Middleware\PreventBackHistory::class,
            'team' => \App\Http\Middleware\EnsureTeamRegistration::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
            'session.timeout' => \App\Http\Middleware\UserSessionTimeout::class,
            'access.window' => \App\Http\Middleware\EnsureAccessWindow::class,
            'app.version' => \App\Http\Middleware\EnforceAppVersion::class,
        ]);

        // Middleware groups
        $middleware->group('admin', [
            'role:admin',
            'preventbackhistory',
        ]);
        
        $middleware->group('user', [
            'role:user',
            'preventbackhistory',
            'session.timeout',
            // Page routes get session.timeout; the JSON endpoints inside this group
            // were unguarded until 2026-09-03, so a team mid-quiz kept answering past
            // an expired access window. access.window answers JSON and covers both.
            'access.window',
        ]);
        
        $middleware->group('quiz', [
            'preventbackhistory',
        ]);

        $middleware->group('auth_pages', [
            'preventbackhistory',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A mismatched CSRF token means the session is gone — nearly always because the
        // user logged out in another tab. Laravel's default "Page Expired" screen is a
        // dead end, so send them to the login page instead.
        //
        // Livewire needs no special case: its client follows a redirected response on its
        // own (see the response.redirected branch in livewire.js), so the same redirect
        // handles both a normal form post and a background component update.
        // NOTE: match on HttpException, not TokenMismatchException. Handler::render()
        // calls prepareException() BEFORE renderViaCallbacks(), and that converts a
        // TokenMismatchException into HttpException(419, ..., previous: $original) — so a
        // callback type-hinted on TokenMismatchException is never reached.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || ! $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return null; // not a CSRF failure — let Laravel handle it normally
            }

            $message = __('Your session has expired. Please log in again.');

            // Real JSON clients (the quiz runtime, the scanner) must still get JSON —
            // an HTML login page would break their parsing. Livewire is excluded here
            // because it wants the redirect.
            if ($request->expectsJson() && ! $request->hasHeader('X-Livewire')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()->guest(route('login'))->with('error', $message);
        });
    })->create();