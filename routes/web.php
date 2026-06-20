<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\Webhooks\PostmarkInboundController;
use App\Livewire\Dashboard;
use App\Livewire\SubmissionStatus;
use App\Livewire\UploadForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $body = request()->getHost() === 'journal.manorhousegardens.org'
        ? "# Internal tool for journal.manorhousegardens.org.\nUser-agent: *\nDisallow: /\n"
        : "# Public site for manorhousegardens.org.\nUser-agent: *\nDisallow:\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

// The record/upload/type intake — signed-in gardeners only.
// Guests hitting this are bounced to the /login landing.
Route::get('/', UploadForm::class)->middleware('auth')->name('home');

// Public: live pipeline status for a submission.
Route::get('/status/{submission:uuid}', SubmissionStatus::class)->name('submissions.status');

// Public: tokenized article view + downloads (no login required).
Route::get('/a/{token}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/a/{token}/download/{format}', [ArticleController::class, 'download'])
    ->whereIn('format', ['md', 'pdf'])
    ->name('articles.download');

// Magic-link auth. /login is the public front door; signed-in folks skip it.
Route::get('/login', fn () => auth()->check() ? redirect()->route('home') : view('auth.login'))->name('login');
Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('auth.magic.send');
Route::get('/auth/login/{user}', [MagicLinkController::class, 'login'])
    ->middleware('signed')
    ->name('auth.magic.login');
Route::post('/auth/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('home');
})->name('auth.logout');

// Gardener dashboard (articles, recordings, writing voice).
Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

// Download a memo's transcript as Markdown (owner only).
Route::get('/memos/{submission:uuid}/transcript', [TranscriptController::class, 'download'])
    ->middleware('auth')
    ->name('memos.transcript');

// Inbound email webhook (CSRF-exempt via bootstrap/app.php).
Route::post('/webhooks/postmark', PostmarkInboundController::class)->name('webhooks.postmark');
