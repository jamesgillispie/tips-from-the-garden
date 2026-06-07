<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceProfile extends Model
{
    protected $fillable = [
        'user_id',
        'profile_text',
        'sample_count',
    ];

    protected function casts(): array
    {
        return [
            'sample_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
