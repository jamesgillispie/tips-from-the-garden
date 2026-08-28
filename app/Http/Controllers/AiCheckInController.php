<?php

namespace App\Http\Controllers;

use App\Mail\AiProcessingStopped;
use App\Models\User;
use App\Services\AiConsentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class AiCheckInController extends Controller
{
    public function show(Request $request, User $user, string $action)
    {
        return view('ai-check-in.confirm', [
            'action' => $action,
            'actionUrl' => $request->fullUrl(),
            'user' => $user,
        ]);
    }

    public function update(
        Request $request,
        User $user,
        string $action,
        AiConsentService $consent,
    ) {
        if ($action === 'disable') {
            $consent->applyBroadChoice($user, enabled: false, analyticsEnabled: false);
            Mail::to($user->email)->queue(new AiProcessingStopped);

            return view('ai-check-in.complete', [
                'heading' => 'AI processing is off',
                'message' => 'New recordings will stay untouched. You can still type and save your own notes, or turn individual features back on from account settings.',
            ]);
        }

        $consent->reaffirm(
            $user,
            analyticsEnabled: $user->aiCheckInChannel() === User::AI_CHECK_IN_BROWSER,
        );

        return view('ai-check-in.complete', [
            'heading' => 'Thanks for checking in',
            'message' => 'Your current choices stay in place. We will check in again next month.',
        ]);
    }
}
