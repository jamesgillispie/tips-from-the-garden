<?php

use App\Http\Middleware\VerifyTurnstile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a tunnel/proxy (Cloudflare), trust forwarded headers so https
        // URLs — including signed password-reset links — generate and validate
        // correctly.
        $middleware->trustProxies(at: '*');

        // vanilla-cookieconsent writes this cookie in JavaScript, so Laravel
        // must not try to decrypt it before registration / Google sign-in read it.
        $middleware->encryptCookies(except: ['cc_cookie']);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Cloudflare Turnstile guards the public auth POSTs (sign in / register
        // / reset request) from one place.
        $middleware->web(append: [
            VerifyTurnstile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
