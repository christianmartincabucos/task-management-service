<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_command_creates_translations(): void
    {
        $this->artisan('translations:seed', ['--count' => 100])
            ->assertExitCode(0);

        $this->assertDatabaseCount('tags', 10);
        $this->assertGreaterThanOrEqual(100, \App\Models\Translation::count());
    }

    public function test_seed_command_fails_with_zero_count(): void
    {
        $this->artisan('translations:seed', ['--count' => 0])
            ->assertExitCode(1);
    }

    public function test_seed_command_creates_tags(): void
    {
        $this->artisan('translations:seed', ['--count' => 10])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
        $this->assertDatabaseHas('tags', ['name' => 'desktop']);
        $this->assertDatabaseHas('tags', ['name' => 'web']);
    }

    public function test_seed_command_assigns_tags_to_translations(): void
    {
        $this->artisan('translations:seed', ['--count' => 50])
            ->assertExitCode(0);

        // Verify some translations have tags
        $translationsWithTags = \App\Models\Translation::has('tags')->count();
        $this->assertGreaterThan(0, $translationsWithTags);
    }
}
