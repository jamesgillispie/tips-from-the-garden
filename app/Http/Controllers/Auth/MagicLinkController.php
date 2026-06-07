<?php

namespace App\Http\Controllers\Auth;

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::fromEmail($validated['email']);

        $loginUrl = URL::temporarySignedRoute(
            'auth.magic.login',
            now()->addMinutes(30),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new MagicLinkMail($loginUrl));

        return back()->with('status', 'Check your email — your sign-in link is on its way.');
    }

    public function login(Request $request, User $user)
    {
        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
