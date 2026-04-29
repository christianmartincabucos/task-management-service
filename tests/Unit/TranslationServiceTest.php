<?php

namespace Tests\Unit;

use App\Models\Translation;
use App\Repositories\TranslationRepository;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TranslationService::class);
    }

    public function test_list_returns_paginated_translations(): void
    {
        Translation::factory()->count(5)->create();

        $result = $this->service->list();

        $this->assertEquals(5, $result->total());
    }

    public function test_list_with_filters(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);

        $result = $this->service->list(['locale' => 'en']);

        $this->assertEquals(1, $result->total());
    }

    public function test_find_returns_translation(): void
    {
        $translation = Translation::factory()->create();

        $found = $this->service->find($translation->id);

        $this->assertEquals($translation->id, $found->id);
    }

    public function test_find_throws_for_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->find(999);
    }

    public function test_create_stores_and_returns_translation(): void
    {
        $data = [
            'key' => 'service.test',
            'locale' => 'en',
            'content' => 'Service test content',
        ];

        $translation = $this->service->create($data);

        $this->assertDatabaseHas('translations', $data);
        $this->assertEquals('service.test', $translation->key);
    }

    public function test_create_invalidates_export_cache(): void
    {
        Cache::put('translations_export_all', ['cached' => 'data'], 3600);

        $this->service->create([
            'key' => 'cache.test',
            'locale' => 'en',
            'content' => 'Cache invalidation test',
        ]);

        $this->assertNull(Cache::get('translations_export_all'));
    }

    public function test_update_modifies_and_returns_translation(): void
    {
        $translation = Translation::factory()->create(['content' => 'Old']);

        $updated = $this->service->update($translation->id, ['content' => 'New']);

        $this->assertEquals('New', $updated->content);
    }

    public function test_update_invalidates_export_cache(): void
    {
        $translation = Translation::factory()->create();
        Cache::put('translations_export_all', ['cached' => 'data'], 3600);

        $this->service->update($translation->id, ['content' => 'Updated']);

        $this->assertNull(Cache::get('translations_export_all'));
    }

    public function test_delete_removes_translation(): void
    {
        $translation = Translation::factory()->create();

        $result = $this->service->delete($translation->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_delete_invalidates_export_cache(): void
    {
        $translation = Translation::factory()->create();
        Cache::put('translations_export_all', ['cached' => 'data'], 3600);

        $this->service->delete($translation->id);

        $this->assertNull(Cache::get('translations_export_all'));
    }

    public function test_export_returns_grouped_structure(): void
    {
        Translation::factory()->create(['key' => 'hello', 'locale' => 'en', 'content' => 'Hello']);
        Translation::factory()->create(['key' => 'hello', 'locale' => 'fr', 'content' => 'Bonjour']);

        $result = $this->service->export();

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('fr', $result);
        $this->assertEquals('Hello', $result['en']['hello']);
        $this->assertEquals('Bonjour', $result['fr']['hello']);
    }

    public function test_export_filters_by_locale(): void
    {
        Translation::factory()->create(['key' => 'hello', 'locale' => 'en', 'content' => 'Hello']);
        Translation::factory()->create(['key' => 'hello', 'locale' => 'fr', 'content' => 'Bonjour']);

        $result = $this->service->export('en');

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayNotHasKey('fr', $result);
    }

    public function test_export_caches_result(): void
    {
        Translation::factory()->create(['key' => 'cached', 'locale' => 'en', 'content' => 'Cached']);

        // First call should cache
        $this->service->export();

        // Verify cache was set
        $this->assertNotNull(Cache::get('translations_export_all'));
    }

    public function test_export_returns_fresh_data_after_create(): void
    {
        Translation::factory()->create(['key' => 'first', 'locale' => 'en', 'content' => 'First']);
        $this->service->export(); // Cache the result

        // Create new translation (should invalidate cache)
        $this->service->create(['key' => 'second', 'locale' => 'en', 'content' => 'Second']);

        $result = $this->service->export();

        $this->assertArrayHasKey('second', $result['en']);
    }
}
