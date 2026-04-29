<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TranslationRepository
{
    /**
     * Get all translations with optional filtering and pagination.
     *
     * @param array<string, mixed> $filters
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Translation::query()->with('tags');

        $this->applyFilters($query, $filters);

        return $query->orderBy('key')
            ->orderBy('locale')
            ->paginate($perPage);
    }

    /**
     * Find a translation by ID with its tags.
     */
    public function findById(int $id): ?Translation
    {
        return Translation::with('tags')->find($id);
    }

    /**
     * Find a translation by ID or fail.
     */
    public function findOrFail(int $id): Translation
    {
        return Translation::with('tags')->findOrFail($id);
    }

    /**
     * Create a new translation.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Translation
    {
        $translation = Translation::create([
            'key' => $data['key'],
            'locale' => $data['locale'],
            'content' => $data['content'],
        ]);

        if (! empty($data['tags'])) {
            $this->syncTags($translation, $data['tags']);
        }

        return $translation->load('tags');
    }

    /**
     * Update an existing translation.
     *
     * @param array<string, mixed> $data
     */
    public function update(Translation $translation, array $data): Translation
    {
        $translation->update(array_filter([
            'key' => $data['key'] ?? null,
            'locale' => $data['locale'] ?? null,
            'content' => $data['content'] ?? null,
        ]));

        if (array_key_exists('tags', $data)) {
            $this->syncTags($translation, $data['tags'] ?? []);
        }

        return $translation->load('tags');
    }

    /**
     * Delete a translation.
     */
    public function delete(Translation $translation): bool
    {
        return (bool) $translation->delete();
    }

    /**
     * Get translations formatted for export.
     * Returns a flat collection optimized for large datasets.
     */
    public function getForExport(?string $locale = null): Collection
    {
        $query = Translation::query()
            ->select(['id', 'key', 'locale', 'content'])
            ->with('tags:id,name');

        if ($locale !== null) {
            $query->byLocale($locale);
        }

        return $query->orderBy('key')->get();
    }

    /**
     * Apply filter scopes to the query builder.
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['locale'])) {
            $query->byLocale($filters['locale']);
        }

        if (! empty($filters['key'])) {
            $query->byKey($filters['key']);
        }

        if (! empty($filters['tag'])) {
            $query->byTag($filters['tag']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
    }

    /**
     * Sync tags by name, creating any that don't exist.
     *
     * @param list<string> $tagNames
     */
    private function syncTags(Translation $translation, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $name) {
            $tag = \App\Models\Tag::firstOrCreate(['name' => trim($name)]);
            $tagIds[] = $tag->id;
        }

        $translation->tags()->sync($tagIds);
    }
}
