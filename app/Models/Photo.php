<?php

namespace App\Models;

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

    protected static function booted(): void
    {
        // Deleting the row is also the revocation mechanism (ADR 0002): the
        // stored objects must go with it or the proxied URLs would keep working.
        static::deleted(function (self $photo) {
            Storage::disk(config('pipeline.photos.disk'))
                ->delete(array_filter([$photo->path, $photo->thumb_path]));
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
