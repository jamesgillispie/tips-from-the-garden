<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingSample extends Model
{
    public const SOURCE_PASTE = 'paste';

    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_TRANSCRIPT = 'transcript';

    public const SOURCE_SEED = 'seed';

    protected $fillable = [
        'user_id',
        'source',
        'title',
        'body',
        'include_in_profile',
    ];

    protected function casts(): array
    {
        return [
            'include_in_profile' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('include_in_profile', true);
    }
}
