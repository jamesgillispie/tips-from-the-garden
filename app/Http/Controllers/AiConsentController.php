<?php

namespace App\Http\Controllers;

use App\Services\AiConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AiConsentController extends Controller
{
    public function __invoke(Request $request, AiConsentService $consent): JsonResponse
    {
        $validated = $request->validate([
            'ai_enabled' => ['required', 'boolean'],
            'analytics_enabled' => ['required', 'boolean'],
            'ai_changed' => ['required', 'boolean'],
            'reaffirmed' => ['required', 'boolean'],
        ]);

        $user = $consent->syncBrowserChoice(
            user: $request->user(),
            aiEnabled: $validated['ai_enabled'],
            analyticsEnabled: $validated['analytics_enabled'],
            aiChanged: $validated['ai_changed'],
            reaffirmed: $validated['reaffirmed'],
        );

        return response()->json([
            'ai_enabled' => $user->usesAnyAi(),
            'check_in_channel' => $user->aiCheckInChannel(),
            'last_ai_check_in_at' => $user->last_ai_check_in_at?->toIso8601String(),
        ]);
    }
}
