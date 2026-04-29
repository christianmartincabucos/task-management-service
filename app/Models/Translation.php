<?php

namespace App\Models;

use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'locale',
        'content',
    ];

    /**
     * Get the tags associated with this translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Scope a query to filter by locale.
     */
    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to filter by key (exact match).
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope a query to filter by tag name.
     */
    public function scopeByTag(Builder $query, string $tag): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tag): void {
            $q->where('name', $tag);
        });
    }

    /**
     * Scope a query to search by content (partial match).
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search): void {
            $q->where('key', 'LIKE', "%{$search}%")
              ->orWhere('content', 'LIKE', "%{$search}%");
        });
    }
}
