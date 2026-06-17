<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use App\Services\Translation\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_replaces_tags_when_provided(): void
    {
        $lang = Language::factory()->create();
        $t = Translation::factory()->create(['language_id' => $lang->id]);
        $t->tags()->attach(Tag::factory()->create(['name' => 'a']));

        app(TranslationService::class)->update($t, ['tags' => ['b', 'c']]);

        $names = $t->fresh()->tags->pluck('name')->sort()->values()->all();
        $this->assertSame(['b', 'c'], $names);
    }

    public function test_update_keeps_tags_when_omitted(): void
    {
        $lang = Language::factory()->create();
        $t = Translation::factory()->create(['language_id' => $lang->id]);
        $t->tags()->attach(Tag::factory()->create(['name' => 'keep']));

        app(TranslationService::class)->update($t, ['content' => 'updated only']);

        $this->assertSame(['keep'], $t->fresh()->tags->pluck('name')->all());
    }

    public function test_search_filters_by_locale_and_tag(): void
    {
        $en = Language::factory()->create(['code' => 'en']);
        $fr = Language::factory()->create(['code' => 'fr']);
        $tag = Tag::factory()->create(['name' => 'mobile']);

        $a = Translation::factory()->create(['language_id' => $en->id]);
        $a->tags()->attach($tag);
        Translation::factory()->create(['language_id' => $en->id]);
        Translation::factory()->create(['language_id' => $fr->id]);

        $results = app(TranslationService::class)->search(['locale' => 'en', 'tag' => 'mobile']);

        $this->assertSame(1, $results->total());
        $this->assertSame($a->id, $results->items()[0]->id);
    }

    public function test_delete_removes_translation(): void
    {
        $t = Translation::factory()->for(Language::factory())->create();

        app(TranslationService::class)->delete($t);

        $this->assertDatabaseMissing('translations', ['id' => $t->id]);
    }
}
