<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Route replies to an address a human actually reads. The "From" can be
        // a send-only address (e.g. hello@), while replies go somewhere that
        // forwards to a real inbox.
        if ($replyTo = config('mail.reply_to.address')) {
            Mail::alwaysReplyTo($replyTo, config('mail.reply_to.name') ?: config('app.name'));
        }
    }
}
