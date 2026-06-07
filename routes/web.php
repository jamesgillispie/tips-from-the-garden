<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Webhooks\PostmarkInboundController;
use App\Livewire\Dashboard;
use App\Livewire\SubmissionStatus;
use App\Livewire\UploadForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public: the front door.
Route::get('/', UploadForm::class)->name('home');

// Public: live pipeline status for a submission.
Route::get('/status/{submission:uuid}', SubmissionStatus::class)->name('submissions.status');

// Public: tokenized article view + downloads (no login required).
Route::get('/a/{token}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/a/{token}/download/{format}', [ArticleController::class, 'download'])
    ->whereIn('format', ['md', 'pdf'])
    ->name('articles.download');

// Magic-link auth.
Route::get('/login', fn () => view('auth.login'))->name('login');
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

// Gardener dashboard (article library + writing samples).
Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

// Inbound email webhook (CSRF-exempt via bootstrap/app.php).
Route::post('/webhooks/postmark', PostmarkInboundController::class)->name('webhooks.postmark');
