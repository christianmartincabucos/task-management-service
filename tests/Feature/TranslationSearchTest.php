<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_search_by_locale(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'search.en']);
        Translation::factory()->create(['locale' => 'fr', 'key' => 'search.fr']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'search.en2']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?locale=en');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_search_by_key(): void
    {
        Translation::factory()->create(['key' => 'auth.login']);
        Translation::factory()->create(['key' => 'auth.register']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?key=auth.login');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('auth.login', $response->json('data.0.key'));
    }

    public function test_search_by_tag(): void
    {
        $translation1 = Translation::factory()->create();
        $translation2 = Translation::factory()->create();
        $mobileTag = Tag::create(['name' => 'mobile']);
        $translation1->tags()->attach($mobileTag);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?tag=mobile');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_search_by_content(): void
    {
        Translation::factory()->create(['content' => 'Welcome to our platform']);
        Translation::factory()->create(['content' => 'Goodbye world']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?search=Welcome');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_search_by_partial_key(): void
    {
        Translation::factory()->create(['key' => 'auth.welcome_message']);
        Translation::factory()->create(['key' => 'nav.home']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?search=welcome');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_combined_filters(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'auth.login', 'content' => 'Login please']);
        Translation::factory()->create(['locale' => 'fr', 'key' => 'auth.login', 'content' => 'Connectez-vous']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'nav.home', 'content' => 'Home']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?locale=en&key=auth.login');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_search_returns_empty_for_no_matches(): void
    {
        Translation::factory()->create(['content' => 'Hello']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?search=nonexistent_term_xyz');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('meta.total'));
    }

    public function test_search_is_case_insensitive_for_like(): void
    {
        Translation::factory()->create(['content' => 'Welcome Message']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?search=welcome');

        $response->assertStatus(200);
        // SQLite LIKE is case-insensitive by default for ASCII
        $this->assertEquals(1, $response->json('meta.total'));
    }
}
