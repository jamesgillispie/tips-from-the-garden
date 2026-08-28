<?php

namespace App\Console\Commands;

use App\Mail\MonthlyAiCheckIn;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAiCheckIns extends Command
{
    protected $signature = 'ai:send-check-ins
        {--force : Send to every email-channel user with active AI, even when not yet due}';

    protected $description = 'Queue monthly AI transparency check-ins for users who prefer email';

    public function handle(): int
    {
        $queued = 0;

        User::query()
            ->where('ai_opt_in', true)
            ->eachById(function (User $user) use (&$queued): void {
                if (! $user->usesAnyAi() || $user->aiCheckInChannel() !== User::AI_CHECK_IN_EMAIL) {
                    return;
                }

                if (! $this->option('force') && ! $user->aiCheckInDue()) {
                    return;
                }

                Mail::to($user->email)->queue(new MonthlyAiCheckIn($user));
                $queued++;
            });

        $this->info("Queued {$queued} AI check-in email(s).");

        return self::SUCCESS;
    }
}
