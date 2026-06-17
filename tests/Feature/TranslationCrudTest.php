<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $token = '';

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = app(TokenService::class)->issue($user, 'test')['token'];
    }

    private function authed(): self
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}");
    }

    public function test_create_translation_with_tags(): void
    {
        $lang = Language::factory()->create(['code' => 'en']);

        $response = $this->authed()->postJson('/api/translations', [
            'language_id' => $lang->id,
            'key'         => 'home.title',
            'content'     => 'Welcome',
            'tags'        => ['web', 'mobile'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'home.title')
            ->assertJsonPath('data.content', 'Welcome');

        $this->assertDatabaseHas('translations', ['key' => 'home.title']);
        $this->assertDatabaseCount('tags', 2);
        $this->assertDatabaseCount('tag_translation', 2);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->authed()->postJson('/api/translations', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['language_id', 'key', 'content']);
    }

    public function test_create_rejects_duplicate_key_per_locale(): void
    {
        $lang = Language::factory()->create();
        $this->authed()->postJson('/api/translations', [
            'language_id' => $lang->id,
            'key'         => 'dup.key',
            'content'     => 'A',
        ])->assertCreated();

        $this->withoutExceptionHandling();
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->authed()->postJson('/api/translations', [
            'language_id' => $lang->id,
            'key'         => 'dup.key',
            'content'     => 'B',
        ]);
    }

    public function test_show_returns_translation_with_language_and_tags(): void
    {
        $lang = Language::factory()->create();
        $translation = Translation::factory()->create(['language_id' => $lang->id]);
        $tag = Tag::factory()->create(['name' => 'mobile']);
        $translation->tags()->attach($tag);

        $this->authed()->getJson("/api/translations/{$translation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $translation->id)
            ->assertJsonPath('data.tags.0', 'mobile')
            ->assertJsonPath('data.language.code', $lang->code);
    }

    public function test_update_translation_and_replace_tags(): void
    {
        $lang = Language::factory()->create();
        $translation = Translation::factory()->create(['language_id' => $lang->id]);
        $translation->tags()->attach(Tag::factory()->create(['name' => 'old']));

        $this->authed()->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated',
            'tags'    => ['new1', 'new2'],
        ])->assertOk()
          ->assertJsonPath('data.content', 'Updated')
          ->assertJsonPath('data.tags', ['new1', 'new2']);
    }

    public function test_delete_translation(): void
    {
        $translation = Translation::factory()
            ->for(Language::factory())
            ->create();

        $this->authed()->deleteJson("/api/translations/{$translation->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }
}
