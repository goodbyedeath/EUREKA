<?php


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            // Legacy middleware (still functional)
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            
            // New optimized middleware
            'role' => \App\Http\Middleware\RoleBasedAccessMiddleware::class,
            'preventbackhistory' => \App\Http\Middleware\PreventBackHistory::class,
            'team' => \App\Http\Middleware\EnsureTeamRegistration::class,
        ]);

        // Middleware groups
        $middleware->group('admin', [
            'role:admin',
            'preventbackhistory',
        ]);
        
        $middleware->group('user', [
            'role:user',
            'preventbackhistory',
        ]);
        
        $middleware->group('quiz', [
            'preventbackhistory',
        ]);

        $middleware->group('auth_pages', [
            'preventbackhistory',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();