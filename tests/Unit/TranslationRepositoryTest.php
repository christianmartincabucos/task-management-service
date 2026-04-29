<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TranslationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TranslationRepository();
    }

    public function test_get_all_returns_paginated_results(): void
    {
        Translation::factory()->count(25)->create();

        $result = $this->repository->getAll([], 10);

        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(25, $result->total());
    }

    public function test_get_all_filters_by_locale(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);

        $result = $this->repository->getAll(['locale' => 'en']);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_filters_by_key(): void
    {
        Translation::factory()->create(['key' => 'auth.login']);
        Translation::factory()->create(['key' => 'nav.home']);

        $result = $this->repository->getAll(['key' => 'auth.login']);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_filters_by_tag(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'mobile']);
        $translation->tags()->attach($tag);

        Translation::factory()->create(); // No tags

        $result = $this->repository->getAll(['tag' => 'mobile']);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_filters_by_search(): void
    {
        Translation::factory()->create(['key' => 'auth.welcome', 'content' => 'Welcome']);
        Translation::factory()->create(['key' => 'nav.home', 'content' => 'Home']);

        $result = $this->repository->getAll(['search' => 'welcome']);

        $this->assertEquals(1, $result->total());
    }

    public function test_get_all_eager_loads_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'web']);
        $translation->tags()->attach($tag);

        $result = $this->repository->getAll();

        $this->assertTrue($result->first()->relationLoaded('tags'));
    }

    public function test_find_by_id_returns_translation(): void
    {
        $translation = Translation::factory()->create();

        $found = $this->repository->findById($translation->id);

        $this->assertNotNull($found);
        $this->assertEquals($translation->id, $found->id);
    }

    public function test_find_by_id_returns_null_for_missing(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    public function test_find_or_fail_throws_for_missing(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->findOrFail(999);
    }

    public function test_create_stores_translation(): void
    {
        $data = [
            'key' => 'test.create',
            'locale' => 'en',
            'content' => 'Created translation',
        ];

        $translation = $this->repository->create($data);

        $this->assertDatabaseHas('translations', $data);
        $this->assertEquals('test.create', $translation->key);
    }

    public function test_create_with_tags(): void
    {
        $data = [
            'key' => 'test.tags',
            'locale' => 'en',
            'content' => 'With tags',
            'tags' => ['mobile', 'web'],
        ];

        $translation = $this->repository->create($data);

        $this->assertCount(2, $translation->tags);
        $this->assertTrue($translation->tags->contains('name', 'mobile'));
        $this->assertTrue($translation->tags->contains('name', 'web'));
    }

    public function test_create_with_tags_creates_new_tags(): void
    {
        $data = [
            'key' => 'test.newtags',
            'locale' => 'en',
            'content' => 'New tags',
            'tags' => ['brand-new-tag'],
        ];

        $this->repository->create($data);

        $this->assertDatabaseHas('tags', ['name' => 'brand-new-tag']);
    }

    public function test_update_modifies_translation(): void
    {
        $translation = Translation::factory()->create(['content' => 'Old content']);

        $updated = $this->repository->update($translation, ['content' => 'New content']);

        $this->assertEquals('New content', $updated->content);
        $this->assertDatabaseHas('translations', ['id' => $translation->id, 'content' => 'New content']);
    }

    public function test_update_syncs_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'old-tag']);
        $translation->tags()->attach($tag);

        $updated = $this->repository->update($translation, ['tags' => ['new-tag']]);

        $this->assertCount(1, $updated->tags);
        $this->assertEquals('new-tag', $updated->tags->first()->name);
    }

    public function test_update_ignores_tags_when_not_provided(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'existing-tag']);
        $translation->tags()->attach($tag);

        $this->repository->update($translation, ['content' => 'Updated']);

        $this->assertCount(1, $translation->fresh()->tags);
    }

    public function test_delete_removes_translation(): void
    {
        $translation = Translation::factory()->create();

        $result = $this->repository->delete($translation);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_get_for_export_returns_all_translations(): void
    {
        Translation::factory()->count(5)->create();

        $result = $this->repository->getForExport();

        $this->assertCount(5, $result);
    }

    public function test_get_for_export_filters_by_locale(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);

        $result = $this->repository->getForExport('en');

        $this->assertCount(1, $result);
        $this->assertEquals('en', $result->first()->locale);
    }

    public function test_get_for_export_eager_loads_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'web']);
        $translation->tags()->attach($tag);

        $result = $this->repository->getForExport();

        $this->assertTrue($result->first()->relationLoaded('tags'));
    }
}
