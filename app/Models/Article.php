<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasRevisions;

    protected $fillable = [
        'title',
        'body_md',
        'user_id',
        'submission_id',
        'article_template_id',
        'writer',
        'download_token',
        'delivered_at',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            $article->download_token ??= Str::random(40);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ArticleTemplate::class, 'article_template_id');
    }

    public function bodyHtml(): string
    {
        return Str::markdown($this->body_md ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function publicUrl(): string
    {
        return route('articles.show', ['token' => $this->download_token]);
    }
}
