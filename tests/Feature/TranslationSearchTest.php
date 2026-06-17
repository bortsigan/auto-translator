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

class TranslationSearchTest extends TestCase
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

    public function test_search_by_locale(): void
    {
        $en = Language::factory()->create(['code' => 'en']);
        $fr = Language::factory()->create(['code' => 'fr']);
        Translation::factory()->create(['language_id' => $en->id, 'key' => 'a.b']);
        Translation::factory()->create(['language_id' => $fr->id, 'key' => 'c.d']);

        $this->authed()->getJson('/api/translations?locale=en')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'a.b');
    }

    public function test_search_by_key_prefix(): void
    {
        $lang = Language::factory()->create();
        Translation::factory()->create(['language_id' => $lang->id, 'key' => 'welcome.message']);
        Translation::factory()->create(['language_id' => $lang->id, 'key' => 'farewell.message']);

        $this->authed()->getJson('/api/translations?key=welcome')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'welcome.message');
    }

    public function test_search_by_tag(): void
    {
        $lang = Language::factory()->create();
        $mobile = Tag::factory()->create(['name' => 'mobile']);
        $web    = Tag::factory()->create(['name' => 'web']);
        $t1 = Translation::factory()->create(['language_id' => $lang->id]);
        $t1->tags()->attach($mobile);
        $t2 = Translation::factory()->create(['language_id' => $lang->id]);
        $t2->tags()->attach($web);

        $this->authed()->getJson('/api/translations?tag=mobile')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $t1->id);
    }

    public function test_search_pagination_limit_is_capped(): void
    {
        $lang = Language::factory()->create();
        Translation::factory()->count(5)->create(['language_id' => $lang->id]);

        $response = $this->authed()->getJson('/api/translations?per_page=2');
        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
