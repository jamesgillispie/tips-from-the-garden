<?php

namespace App\Models;

use A17\Twill\Models\Model;
use App\Mail\SubmissionFailed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Submission extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_TRANSCRIBING = 'transcribing';

    public const STATUS_TRANSCRIBED = 'transcribed';

    public const STATUS_WRITING = 'writing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const SOURCE_EMAIL = 'email';

    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_PASTE = 'paste';

    public const SOURCE_RECORD = 'record';

    protected $fillable = [
        'uuid',
        'user_id',
        'source',
        'audio_path',
        'original_filename',
        'status',
        'error',
        'published',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $submission) {
            $submission->uuid ??= (string) Str::uuid();
            $submission->status ??= self::STATUS_RECEIVED;
            $submission->published = true;
        });

        // A hard delete (Twill admin destroy) would otherwise drop the photo
        // rows through the FK cascade without firing Photo's deleted event —
        // orphaning the stored objects the deletion is meant to revoke.
        static::forceDeleting(function (self $submission) {
            $submission->photos()->get()->each->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcript(): HasOne
    {
        return $this->hasOne(Transcript::class);
    }

    public function article(): HasOne
    {
        return $this->hasOne(Article::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function markAs(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * Mark the submission failed and let the gardener know by email.
     * Both the chain's catch and each job's failed() hook can land here,
     * so the first transition wins and later calls are no-ops — the
     * gardener only ever hears about a failure once.
     */
    public function markFailed(string $error): void
    {
        if ($this->isFailed()) {
            return;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => Str::limit($error, 2000),
        ]);

        try {
            if ($this->user) {
                Mail::to($this->user->email)->queue(new SubmissionFailed($this));
            }
        } catch (\Throwable $e) {
            Log::warning('Could not send the submission-failed email', [
                'submission_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED => 'Received — waiting in line',
            self::STATUS_TRANSCRIBING => 'Listening to your memo…',
            self::STATUS_TRANSCRIBED => 'Transcribed — warming up the writer',
            self::STATUS_WRITING => 'Writing your journal entry…',
            self::STATUS_READY => 'Your journal entry is ready!',
            self::STATUS_FAILED => 'Something went wrong',
            default => $this->status,
        };
    }
}
