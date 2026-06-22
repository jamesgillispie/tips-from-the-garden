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
        'region',
        'birth_year',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_year' => 'integer',
        ];
    }

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
     * Find or create a user from an email address. Registration adds a password
     * on top; the email and webhook intake doors leave it null until the
     * gardener claims the account with a password-reset link.
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

    /**
     * Look up an existing account by email — never creates one. The inbound
     * email door uses this so a memo from an unknown (or spoofed) sender can't
     * conjure a ghost account; it has to match an address that already signed up.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', strtolower(trim($email)))->first();
    }
}
