<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $cors = config('cors');
                if ($cors && isset($cors['allowed_origins'])) {
                    $origin = $request->header('Origin');
                    if (in_array('*', $cors['allowed_origins'])) {
                        $response->headers->set('Access-Control-Allow-Origin', '*');
                    } elseif ($origin && in_array($origin, $cors['allowed_origins'])) {
                        $response->headers->set('Access-Control-Allow-Origin', $origin);
                    } elseif (!empty($cors['allowed_origins'])) {
                        $response->headers->set('Access-Control-Allow-Origin', $cors['allowed_origins'][0]);
                    }
                    $response->headers->set('Access-Control-Allow-Methods', implode(', ', $cors['allowed_methods'] ?? ['*']));
                    $response->headers->set('Access-Control-Allow-Headers', implode(', ', $cors['allowed_headers'] ?? ['*']));
                    if ($cors['supports_credentials'] ?? false) {
                        $response->headers->set('Access-Control-Allow-Credentials', 'true');
                    }
                    $response->headers->set('Vary', 'Origin');
                }
            }
            return $response;
        });
    })->create();
