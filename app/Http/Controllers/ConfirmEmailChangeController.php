<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Lands a gardener who clicked the confirmation link we sent to their *new*
 * address. The signed URL proves the link is ours; the hash ties it to the
 * exact pending change, so a stale or superseded link quietly no-ops.
 */
class ConfirmEmailChangeController extends Controller
{
    public function __invoke(Request $request, User $user, string $hash)
    {
        // Dead link: the change was already confirmed, cancelled, or replaced by
        // a newer request (which would carry a different hash).
        if (! $user->pending_email || ! hash_equals(sha1($user->pending_email), $hash)) {
            return redirect()->route('login')
                ->with('status', 'That email confirmation link is no longer valid.');
        }

        // Someone else may have claimed the address between request and click.
        $taken = User::where('email', $user->pending_email)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($taken) {
            $user->forceFill(['pending_email' => null])->save();

            return redirect()->route('login')
                ->with('status', 'That email address is no longer available.');
        }

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
        ])->save();

        // Same browser they're signed into → back to settings. Otherwise (a link
        // opened on another device) → sign in with the new address.
        if (Auth::check() && Auth::id() === $user->getKey()) {
            return redirect()->route('account')
                ->with('status', 'Your email address is now '.$user->email.'.');
        }

        return redirect()->route('login')
            ->with('status', 'Your email address has been updated — sign in with '.$user->email.'.');
    }
}
