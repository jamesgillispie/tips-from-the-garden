<?php

namespace App\Http\Controllers\Webhooks;

use App\Mail\NoAccountFound;
use App\Mail\NoAudioFound;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PostmarkInboundController extends Controller
{
    /**
     * Postmark inbound webhook. Configure the URL as:
     *   https://your-host/webhooks/postmark?token=POSTMARK_INBOUND_TOKEN
     *
     * Always returns 200 for parseable-but-unusable mail so Postmark
     * doesn't retry forever; returns 403 only on a bad token.
     */
    public function __invoke(Request $request, SubmissionService $service)
    {
        $expected = config('services.postmark.inbound_token');

        if ($expected && ! hash_equals($expected, (string) $request->query('token'))) {
            abort(403);
        }

        $email = $request->input('FromFull.Email') ?? $request->input('From');

        if (! $email) {
            Log::warning('Inbound email without a sender — ignored.');

            return response()->json(['status' => 'ignored']);
        }

        $attachment = collect($request->input('Attachments', []))
            ->first(function (array $attachment) {
                $type = strtolower($attachment['ContentType'] ?? '');
                $ext = strtolower(pathinfo($attachment['Name'] ?? '', PATHINFO_EXTENSION));

                return str_starts_with($type, 'audio/')
                    || in_array($ext, config('pipeline.audio.mimes'), true);
            });

        if (! $attachment) {
            Log::info('Inbound email had no audio attachment.', ['from' => $email]);

            // Tell the sender what happened instead of going quiet — but never
            // reply to addresses that look like machines, or we risk mail loops.
            if (! $this->looksAutomated($email)) {
                Mail::to($email)->queue(new NoAudioFound);
            }

            return response()->json(['status' => 'no-audio']);
        }

        // A memo is filed by the address it came FROM, so that address has to
        // belong to a real account. We never create one here: an unknown (or
        // spoofed) sender can't conjure a ghost account or slip a memo onto
        // someone else's desk — they're pointed at sign-up instead.
        $user = User::findByEmail($email);

        if (! $user) {
            Log::info('Inbound email from an address with no account.', ['from' => $email]);

            if (! $this->looksAutomated($email)) {
                Mail::to($email)->queue(new NoAccountFound);
            }

            return response()->json(['status' => 'no-account']);
        }

        // Photos snapped alongside the memo ride in as image attachments.
        // They're optional extras — the storer caps and skips as needed.
        $photos = collect($request->input('Attachments', []))
            ->filter(function (array $attachment) {
                $type = strtolower($attachment['ContentType'] ?? '');
                $ext = strtolower(pathinfo($attachment['Name'] ?? '', PATHINFO_EXTENSION));

                return str_starts_with($type, 'image/')
                    || in_array($ext, config('pipeline.photos.mimes'), true);
            })
            ->values()
            ->all();

        $submission = $service->fromEmail(
            user: $user,
            filename: $attachment['Name'] ?? 'memo.m4a',
            base64Content: $attachment['Content'] ?? '',
            photoAttachments: $photos,
        );

        return response()->json(['status' => 'queued', 'submission' => $submission->uuid]);
    }

    protected function looksAutomated(string $email): bool
    {
        return (bool) preg_match(
            '/^(no-?reply|do-?not-?reply|mailer-daemon|postmaster|bounce|auto)/i',
            $email,
        );
    }
}
