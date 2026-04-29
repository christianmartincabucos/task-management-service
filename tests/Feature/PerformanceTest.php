<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_responds_under_200ms(): void
    {
        // Create a reasonable dataset for performance testing
        Translation::factory()->count(100)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations');

        $elapsed = (microtime(true) - $start) * 1000; // ms

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Index responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_show_responds_under_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Show responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_store_responds_under_200ms(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'perf.test',
                'locale' => 'en',
                'content' => 'Performance test',
                'tags' => ['web'],
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(200, $elapsed, "Store responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_update_responds_under_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Updated for performance',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Update responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_destroy_responds_under_200ms(): void
    {
        $translation = Translation::factory()->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Destroy responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_search_responds_under_200ms(): void
    {
        Translation::factory()->count(100)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?search=auth');

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Search responded in {$elapsed}ms (expected < 200ms)");
    }

    public function test_export_responds_under_500ms(): void
    {
        // Create a moderate dataset for export performance testing
        Translation::factory()->count(500)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export');

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, "Export responded in {$elapsed}ms (expected < 500ms)");
    }

    public function test_export_with_locale_filter_responds_under_500ms(): void
    {
        Translation::factory()->count(500)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/export/en');

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, "Export with locale responded in {$elapsed}ms (expected < 500ms)");
    }

    public function test_filter_by_tag_responds_under_200ms(): void
    {
        $translations = Translation::factory()->count(50)->create();
        $tag = Tag::create(['name' => 'perf-tag']);

        // Attach tag to first 25 translations
        $translations->take(25)->each(fn ($t) => $t->tags()->attach($tag));

        $start = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?tag=perf-tag');

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, "Tag filter responded in {$elapsed}ms (expected < 200ms)");
    }
}
