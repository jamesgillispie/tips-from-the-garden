<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleTemplate extends Model
{
    use HasRevisions;

    protected $fillable = [
        'title',
        'description',
        'structure_prompt',
        'example_skeleton',
        'published',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /**
     * Pick the template for a new article. v1: first active template;
     * later this could match on transcript content.
     */
    public static function pick(): ?self
    {
        return static::active()->orderBy('id')->first();
    }
}
