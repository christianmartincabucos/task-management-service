<?php

namespace App\Services;

use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    /**
     * Cache key prefix for export data.
     */
    private const EXPORT_CACHE_PREFIX = 'translations_export';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const EXPORT_CACHE_TTL = 3600;

    public function __construct(
        private readonly TranslationRepository $repository,
    ) {}

    /**
     * List translations with optional filters and pagination.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $perPage);
    }

    /**
     * Get a single translation by ID.
     */
    public function find(int $id): Translation
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Create a new translation and invalidate export cache.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Translation
    {
        $translation = $this->repository->create($data);
        $this->invalidateExportCache();

        return $translation;
    }

    /**
     * Update a translation and invalidate export cache.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Translation
    {
        $translation = $this->repository->findOrFail($id);
        $result = $this->repository->update($translation, $data);
        $this->invalidateExportCache();

        return $result;
    }

    /**
     * Delete a translation and invalidate export cache.
     */
    public function delete(int $id): bool
    {
        $translation = $this->repository->findOrFail($id);
        $result = $this->repository->delete($translation);
        $this->invalidateExportCache();

        return $result;
    }

    /**
     * Export translations as a structured JSON-ready array.
     * Results are cached for performance. Always returns fresh data
     * after any write operation.
     *
     * @return array<string, array<string, string>>
     */
    public function export(?string $locale = null): array
    {
        $cacheKey = $this->getExportCacheKey($locale);

        return Cache::remember($cacheKey, self::EXPORT_CACHE_TTL, function () use ($locale): array {
            $translations = $this->repository->getForExport($locale);

            $result = [];
            foreach ($translations as $translation) {
                $result[$translation->locale][$translation->key] = $translation->content;
            }

            return $result;
        });
    }

    /**
     * Invalidate all export caches.
     */
    private function invalidateExportCache(): void
    {
        // Clear the general export cache
        Cache::forget($this->getExportCacheKey(null));

        // Clear locale-specific caches for common locales
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'ja', 'zh', 'ko', 'ar', 'ru'];
        foreach ($locales as $locale) {
            Cache::forget($this->getExportCacheKey($locale));
        }

        // Also clear using a tag if the cache driver supports it
        try {
            Cache::tags([self::EXPORT_CACHE_PREFIX])->flush();
        } catch (\BadMethodCallException) {
            // Cache driver doesn't support tags, individual keys already cleared
        }
    }

    /**
     * Generate the cache key for export data.
     */
    private function getExportCacheKey(?string $locale): string
    {
        return self::EXPORT_CACHE_PREFIX . '_' . ($locale ?? 'all');
    }
}
