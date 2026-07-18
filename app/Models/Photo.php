<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    protected $fillable = [
        'submission_id',
        'path',
        'thumb_path',
        'original_filename',
    ];

    /** The private disk every photo lives on (S3 in production — ADR 0002). */
    public static function storage(): Filesystem
    {
        return Storage::disk(config('pipeline.photos.disk'));
    }

    protected static function booted(): void
    {
        // Deleting the row is also the revocation mechanism (ADR 0002): the
        // stored objects must go with it or the proxied URLs would keep working.
        static::deleted(function (self $photo) {
            self::storage()->delete(array_filter([$photo->path, $photo->thumb_path]));
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
