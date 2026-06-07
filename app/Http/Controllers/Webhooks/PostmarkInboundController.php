<?php

namespace App\Http\Controllers\Webhooks;

use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

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
        $name = $request->input('FromFull.Name');

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
            Log::info('Inbound email had no audio attachment — ignored.', ['from' => $email]);

            return response()->json(['status' => 'no-audio']);
        }

        $submission = $service->fromEmail(
            email: $email,
            name: $name,
            filename: $attachment['Name'] ?? 'memo.m4a',
            base64Content: $attachment['Content'] ?? '',
        );

        return response()->json(['status' => 'queued', 'submission' => $submission->uuid]);
    }
}
