<?php

namespace App\Models;

use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function markAs(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => Str::limit($error, 2000),
        ]);
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
            self::STATUS_WRITING => 'Writing your article…',
            self::STATUS_READY => 'Your article is ready!',
            self::STATUS_FAILED => 'Something went wrong',
            default => $this->status,
        };
    }
}
