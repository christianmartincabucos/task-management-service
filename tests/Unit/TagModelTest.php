<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_can_be_created(): void
    {
        $tag = Tag::create(['name' => 'mobile']);

        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
        $this->assertInstanceOf(Tag::class, $tag);
    }

    public function test_tag_has_translations_relationship(): void
    {
        $tag = Tag::create(['name' => 'web']);
        $translation = Translation::factory()->create();

        $tag->translations()->attach($translation);

        $this->assertCount(1, $tag->translations);
        $this->assertEquals($translation->id, $tag->translations->first()->id);
    }

    public function test_tag_name_is_unique(): void
    {
        Tag::create(['name' => 'unique-tag']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Tag::create(['name' => 'unique-tag']);
    }

    public function test_tag_uses_factory(): void
    {
        $tag = Tag::factory()->create();

        $this->assertNotNull($tag->name);
    }

    public function test_tag_fillable_attributes(): void
    {
        $tag = new Tag();
        $this->assertEquals(['name'], $tag->getFillable());
    }
}
