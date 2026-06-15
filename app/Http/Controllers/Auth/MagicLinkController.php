<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Support\MagicLink;
use App\Support\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Stop bots from firing magic-link emails in bulk.
        if (! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()
                ->withErrors(['turnstile' => 'Please tick the "I\'m human" box and try again.'])
                ->withInput();
        }

        $loginUrl = MagicLink::sendTo(User::fromEmail($validated['email']));

        return back()
            ->with('status', 'Check your email — your sign-in link is on its way.')
            // Local dev sends mail to a log, so hand the link straight to the page.
            ->with('devLoginUrl', app()->isLocal() ? $loginUrl : null);
    }

    public function login(Request $request, User $user)
    {
        Auth::login($user, remember: true);

        $request->session()->regenerate();

        // A deep-linked tab (e.g. ?tab=recordings) wins; otherwise honor the page
        // they were headed to before the login wall, falling back to the record page.
        $tab = $request->query('tab');

        if (in_array($tab, MagicLink::TABS, true)) {
            return redirect()->route('dashboard', ['tab' => $tab]);
        }

        return redirect()->intended(route('home'));
    }
}
