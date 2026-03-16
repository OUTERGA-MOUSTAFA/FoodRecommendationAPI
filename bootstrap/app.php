<?php

// use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        //
    })
    // ->withExceptions(function (Exceptions $exceptions) {
    //     $exceptions->render(function (AuthorizationException $e, $request) {
    //         if ($request->is('api/*')) { // if it API request
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Not have authorization',
    //             ], 403);
    //         }

    //         return response()->view('errors.403', [], 403);
    //     });
    // })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
