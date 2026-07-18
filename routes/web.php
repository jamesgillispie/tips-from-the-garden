<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ConfirmEmailChangeController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\Webhooks\PostmarkInboundController;
use App\Livewire\AccountSettings;
use App\Livewire\Dashboard;
use App\Livewire\SubmissionStatus;
use App\Livewire\UploadForm;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $body = request()->getHost() === 'journal.manorhousegardens.org'
        ? "# Internal tool for journal.manorhousegardens.org.\nUser-agent: *\nDisallow: /\n"
        : "# Public site for manorhousegardens.org.\nUser-agent: *\nDisallow:\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

// The record/upload/type intake — signed-in gardeners only. Guests are bounced
// to Fortify's /login.
Route::get('/', UploadForm::class)->middleware('auth')->name('home');

// Public: live pipeline status for a submission.
Route::get('/status/{submission:uuid}', SubmissionStatus::class)->name('submissions.status');

// Public: tokenized article view + downloads (no login required).
Route::get('/a/{token}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/a/{token}/download/{format}', [ArticleController::class, 'download'])
    ->whereIn('format', ['md', 'pdf'])
    ->name('articles.download');

// Photos are proxied from a private disk, gated by the same entry token
// (ADR 0002). These URLs are baked into delivered emails, so the scheme is
// effectively permanent — don't move it.
Route::get('/a/{token}/photo/{photo}/{size?}', [ArticleController::class, 'photo'])
    ->whereIn('size', ['thumb'])
    ->name('articles.photo');

// Auth — login, register, password reset, logout — is provided by Laravel
// Fortify. Views are wired up in App\Providers\FortifyServiceProvider, and the
// Cloudflare Turnstile gate lives in App\Http\Middleware\VerifyTurnstile.

// "Sign in with Google" via Socialite, sitting alongside Fortify. The callback
// path must match the authorized redirect URI on the Google Cloud OAuth client.
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// Gardener dashboard (articles, recordings, writing voice).
Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

// Download a memo's transcript as Markdown (owner only).
Route::get('/memos/{submission:uuid}/transcript', [TranscriptController::class, 'download'])
    ->middleware('auth')
    ->name('memos.transcript');

// Account self-service: details, email, password, and the danger zone.
Route::get('/account', AccountSettings::class)
    ->middleware('auth')
    ->name('account');

// Confirm an email change from the new address. The signed link is the proof,
// so no login is required — it works on whatever device opened the email.
Route::get('/account/email/confirm/{user}/{hash}', ConfirmEmailChangeController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('account.email.confirm');

// Inbound email webhook (CSRF-exempt via bootstrap/app.php).
Route::post('/webhooks/postmark', PostmarkInboundController::class)->name('webhooks.postmark');
