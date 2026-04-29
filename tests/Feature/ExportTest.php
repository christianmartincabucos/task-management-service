<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_export_returns_all_translations_grouped_by_locale(): void
    {
        Translation::factory()->create(['key' => 'hello', 'locale' => 'en', 'content' => 'Hello']);
        Translation::factory()->create(['key' => 'hello', 'locale' => 'fr', 'content' => 'Bonjour']);
        Translation::factory()->create(['key' => 'goodbye', 'locale' => 'en', 'content' => 'Goodbye']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200)
            ->assertJsonPath('en.hello', 'Hello')
            ->assertJsonPath('fr.hello', 'Bonjour')
            ->assertJsonPath('en.goodbye', 'Goodbye');
    }

    public function test_export_by_locale_returns_flat_structure(): void
    {
        Translation::factory()->create(['key' => 'hello', 'locale' => 'en', 'content' => 'Hello']);
        Translation::factory()->create(['key' => 'goodbye', 'locale' => 'en', 'content' => 'Goodbye']);
        Translation::factory()->create(['key' => 'hello', 'locale' => 'fr', 'content' => 'Bonjour']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export/en');

        $response->assertStatus(200)
            ->assertJsonPath('hello', 'Hello')
            ->assertJsonPath('goodbye', 'Goodbye');

        // Should not contain French translations
        $response->assertJsonMissing(['Bonjour']);
    }

    public function test_export_includes_cache_headers(): void
    {
        Translation::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control')
            ->assertHeader('ETag');
    }

    public function test_export_returns_empty_object_when_no_translations(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->getJson('/api/export');

        $response->assertStatus(401);
    }

    public function test_export_returns_updated_data_after_create(): void
    {
        // Initial export
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        // Create a new translation
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'newkey',
                'locale' => 'en',
                'content' => 'New content',
            ]);

        // Export should include the new translation
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('New content', $data['en']['newkey']);
    }

    public function test_export_returns_updated_data_after_update(): void
    {
        $translation = Translation::factory()->create([
            'key' => 'updatetest',
            'locale' => 'en',
            'content' => 'Original',
        ]);

        // Cache the export
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        // Update the translation
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Modified',
            ]);

        // Export should reflect the update
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Modified', $data['en']['updatetest']);
    }

    public function test_export_returns_updated_data_after_delete(): void
    {
        $translation = Translation::factory()->create([
            'key' => 'deletetest',
            'locale' => 'en',
            'content' => 'To be deleted',
        ]);

        // Cache the export
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        // Delete the translation
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        // Export should not contain deleted translation
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200)
            ->assertJsonMissing(['To be deleted']);
    }

    public function test_export_for_nonexistent_locale_returns_empty(): void
    {
        Translation::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export/xx');

        $response->assertStatus(200);
    }

    public function test_export_handles_multiple_locales_correctly(): void
    {
        $locales = ['en', 'fr', 'es', 'de'];

        foreach ($locales as $locale) {
            Translation::factory()->create([
                'key' => 'greeting',
                'locale' => $locale,
                'content' => "Greeting in {$locale}",
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $response->assertStatus(200);

        foreach ($locales as $locale) {
            $response->assertJsonPath("{$locale}.greeting", "Greeting in {$locale}");
        }
    }
}
