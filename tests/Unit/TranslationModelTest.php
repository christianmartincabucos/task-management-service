<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_can_be_created_with_fillable_attributes(): void
    {
        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);

        $this->assertDatabaseHas('translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);
        $this->assertInstanceOf(Translation::class, $translation);
    }

    public function test_translation_has_tags_relationship(): void
    {
        $translation = Translation::factory()->create();
        $tag = Tag::create(['name' => 'web']);

        $translation->tags()->attach($tag);

        $this->assertCount(1, $translation->tags);
        $this->assertEquals('web', $translation->tags->first()->name);
    }

    public function test_translation_can_have_multiple_tags(): void
    {
        $translation = Translation::factory()->create();
        $tag1 = Tag::create(['name' => 'mobile']);
        $tag2 = Tag::create(['name' => 'desktop']);

        $translation->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $translation->fresh()->tags);
    }

    public function test_scope_by_locale_filters_correctly(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);
        Translation::factory()->create(['locale' => 'en']);

        $results = Translation::byLocale('en')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($t) => $this->assertEquals('en', $t->locale));
    }

    public function test_scope_by_key_filters_correctly(): void
    {
        Translation::factory()->create(['key' => 'auth.login']);
        Translation::factory()->create(['key' => 'auth.register']);

        $results = Translation::byKey('auth.login')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('auth.login', $results->first()->key);
    }

    public function test_scope_by_tag_filters_correctly(): void
    {
        $translation1 = Translation::factory()->create();
        $translation2 = Translation::factory()->create();
        $tag = Tag::create(['name' => 'mobile']);

        $translation1->tags()->attach($tag);

        $results = Translation::byTag('mobile')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($translation1->id, $results->first()->id);
    }

    public function test_scope_search_finds_by_key(): void
    {
        Translation::factory()->create(['key' => 'auth.welcome_message']);
        Translation::factory()->create(['key' => 'nav.home']);

        $results = Translation::search('welcome')->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('welcome', $results->first()->key);
    }

    public function test_scope_search_finds_by_content(): void
    {
        Translation::factory()->create(['content' => 'Welcome to our platform']);
        Translation::factory()->create(['content' => 'Goodbye']);

        $results = Translation::search('Welcome')->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Welcome', $results->first()->content);
    }

    public function test_translation_uses_factory(): void
    {
        $translation = Translation::factory()->create();

        $this->assertNotNull($translation->key);
        $this->assertNotNull($translation->locale);
        $this->assertNotNull($translation->content);
    }

    public function test_translation_factory_with_locale(): void
    {
        $translation = Translation::factory()->locale('fr')->create();

        $this->assertEquals('fr', $translation->locale);
    }

    public function test_translation_factory_with_key(): void
    {
        $translation = Translation::factory()->key('custom.key')->create();

        $this->assertEquals('custom.key', $translation->key);
    }

    public function test_translation_timestamps_are_set(): void
    {
        $translation = Translation::factory()->create();

        $this->assertNotNull($translation->created_at);
        $this->assertNotNull($translation->updated_at);
    }
}
