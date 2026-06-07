<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transcript extends Model
{
    protected $fillable = [
        'submission_id',
        'raw_text',
        'transcriber',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'float',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
