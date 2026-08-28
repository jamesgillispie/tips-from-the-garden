<?php

namespace App\Console\Commands;

use App\Jobs\DeliverArticle;
use App\Jobs\TranscribeAudio;
use App\Jobs\WriteArticle;
use App\Models\Submission;
use App\Models\User;
use App\Services\AiConsentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RunPipeline extends Command
{
    protected $signature = 'pipeline:run
        {audio : Path to a local audio file}
        {--email=dev@example.test : Email of the (created-if-missing) user}
        {--deliver : Also send the article-ready email}';

    protected $description = 'Run the full voice-to-article pipeline synchronously on a local audio file';

    public function handle(AiConsentService $consent): int
    {
        $audioArg = $this->argument('audio');

        if (! is_file($audioArg)) {
            $this->error("File not found: {$audioArg}");

            return self::FAILURE;
        }

        $user = User::fromEmail($this->option('email'));

        // This developer-only command is itself an explicit instruction to run
        // the full model pipeline. Keep its historical one-command workflow by
        // recording that choice on the local test account.
        if (! $user->canTranscribe() || ! $user->canWriteArticles()) {
            $consent->applyBroadChoice($user, enabled: true, analyticsEnabled: false);
            $this->warn('Enabled AI processing for this local pipeline account.');
        }

        $extension = strtolower(pathinfo($audioArg, PATHINFO_EXTENSION)) ?: 'm4a';
        $path = config('pipeline.audio.path').'/'.Str::uuid().'.'.$extension;
        Storage::disk(config('pipeline.audio.disk'))->put($path, file_get_contents($audioArg));

        $submission = Submission::create([
            'user_id' => $user->id,
            'source' => Submission::SOURCE_UPLOAD,
            'audio_path' => $path,
            'original_filename' => basename($audioArg),
        ]);

        $this->info("Submission {$submission->uuid} created. Transcribing…");
        TranscribeAudio::dispatchSync($submission);

        $this->info('Transcribed. Writing article…');
        WriteArticle::dispatchSync($submission);

        if ($this->option('deliver')) {
            DeliverArticle::dispatchSync($submission);
            $this->info('Delivery email sent.');
        }

        $article = $submission->fresh()->article;

        $this->newLine();
        $this->line('<options=bold># '.$article->title.'</>');
        $this->newLine();
        $this->line($article->body_md);
        $this->newLine();
        $this->info('Public URL: '.$article->publicUrl());

        return self::SUCCESS;
    }
}
