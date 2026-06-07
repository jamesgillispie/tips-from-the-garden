<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function voiceProfile(): HasOne
    {
        return $this->hasOne(VoiceProfile::class);
    }

    public function writingSamples(): HasMany
    {
        return $this->hasMany(WritingSample::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Find or create a user from an email address (the only identity we need).
     */
    public static function fromEmail(string $email, ?string $name = null): self
    {
        $user = static::firstOrCreate(
            ['email' => strtolower(trim($email))],
            ['name' => $name],
        );

        $user->voiceProfile()->firstOrCreate([]);

        return $user;
    }
}
