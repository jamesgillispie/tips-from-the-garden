<?php

namespace App\Support;

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * One place that mints a passwordless sign-in link and emails it. Both the
 * /login form and the homepage "Sign in to hear your article" flow lean on
 * this so the two doors stay identical.
 */
class MagicLink
{
    /**
     * The dashboard tabs a sign-in link is allowed to land on. Anything else
     * is ignored, so the signed URL can never be used to bounce a gardener
     * somewhere unexpected.
     */
    public const TABS = ['articles', 'recordings', 'voice'];

    /**
     * Mint a sign-in link, email it, and return the URL — handy in local dev
     * where mail goes to a log and we want to surface the link on screen.
     */
    public static function sendTo(User $user, ?string $tab = null): string
    {
        $params = ['user' => $user->id];

        if ($tab !== null && in_array($tab, self::TABS, true)) {
            $params['tab'] = $tab;
        }

        $loginUrl = URL::temporarySignedRoute(
            'auth.magic.login',
            now()->addMinutes(30),
            $params,
        );

        Mail::to($user->email)->send(new MagicLinkMail($loginUrl));

        return $loginUrl;
    }
}
