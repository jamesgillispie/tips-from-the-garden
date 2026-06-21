<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * One Cloudflare Turnstile gate for every public auth POST — sign in, register,
 * and the "email me a reset link" form. Keeping it in a single middleware lets
 * the Fortify actions stay stock and gives one spot that knows the widget
 * exists. (Reset-password itself is reached from an emailed link, so it's off
 * the list.)
 */
class VerifyTurnstile
{
    /** Fortify POST paths the widget protects (default, unprefixed routes). */
    private const GUARDED = ['login', 'register', 'forgot-password'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->is(...self::GUARDED)) {
            if (! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
                throw ValidationException::withMessages([
                    'turnstile' => 'Please tick the "I\'m human" box and try again.',
                ]);
            }
        }

        return $next($request);
    }
}
