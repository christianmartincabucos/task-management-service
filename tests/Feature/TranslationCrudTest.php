<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── INDEX ──────────────────────────────────────────────────────

    public function test_index_returns_paginated_translations(): void
    {
        Translation::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'key', 'locale', 'content', 'tags', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_paginates_with_custom_per_page(): void
    {
        Translation::factory()->count(15)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_index_caps_per_page_at_100(): void
    {
        Translation::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?per_page=200');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/translations');

        $response->assertStatus(401);
    }

    // ─── STORE ──────────────────────────────────────────────────────

    public function test_store_creates_translation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'auth.login',
                'locale' => 'en',
                'content' => 'Log in to your account',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.key', 'auth.login')
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.content', 'Log in to your account');

        $this->assertDatabaseHas('translations', [
            'key' => 'auth.login',
            'locale' => 'en',
        ]);
    }

    public function test_store_creates_translation_with_tags(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'nav.home',
                'locale' => 'en',
                'content' => 'Home',
                'tags' => ['mobile', 'web'],
            ]);

        $response->assertStatus(201);

        $tags = $response->json('data.tags');
        $this->assertContains('mobile', $tags);
        $this->assertContains('web', $tags);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'locale', 'content']);
    }

    public function test_store_validates_key_max_length(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => str_repeat('a', 256),
                'locale' => 'en',
                'content' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_store_validates_locale_max_length(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'toolonglocale',
                'content' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    // ─── SHOW ──────────────────────────────────────────────────────

    public function test_show_returns_single_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $translation->id)
            ->assertJsonPath('data.key', $translation->key);
    }

    public function test_show_returns_404_for_missing_translation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/99999');

        $response->assertStatus(404);
    }

    public function test_show_includes_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'mobile']);
        $translation->tags()->attach($tag);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.tags.0', 'mobile');
    }

    // ─── UPDATE ──────────────────────────────────────────────────────

    public function test_update_modifies_translation(): void
    {
        $translation = Translation::factory()->create([
            'content' => 'Old content',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Updated content');

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_update_can_change_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'old-tag']);
        $translation->tags()->attach($tag);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/translations/{$translation->id}", [
                'tags' => ['new-tag'],
            ]);

        $response->assertStatus(200);
        $this->assertContains('new-tag', $response->json('data.tags'));
        $this->assertNotContains('old-tag', $response->json('data.tags'));
    }

    public function test_update_returns_404_for_missing_translation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/translations/99999', [
                'content' => 'Updated',
            ]);

        $response->assertStatus(404);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────

    public function test_destroy_deletes_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Translation deleted successfully.']);

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_destroy_returns_404_for_missing_translation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/translations/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_also_removes_pivot_records(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'test-tag']);
        $translation->tags()->attach($tag);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $this->assertDatabaseMissing('tag_translation', [
            'translation_id' => $translation->id,
        ]);
    }
}
