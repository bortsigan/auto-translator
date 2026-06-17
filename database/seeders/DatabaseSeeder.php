<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Factories\TranslationFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach ([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'es', 'name' => 'Spanish'],
        ] as $lang) {
            Language::query()->firstOrCreate(
                ['code' => $lang['code']],
                $lang + ['is_active' => true],
            );
        }

        foreach (['mobile', 'desktop', 'web'] as $tag) {
            Tag::query()->firstOrCreate(['name' => $tag]);
        }

        $tagIds = Tag::query()->pluck('id')->all();

        $languages = Language::all();

        foreach ($languages as $language) {
            TranslationFactory::resetPool();

            $translations = Translation::factory()
                ->count(55)
                ->for($language)
                ->forLocale($language->code)
                ->create();

            foreach ($translations as $translation) {
                if (count($tagIds) === 0) {
                    continue;
                }

                $randomTagId = $tagIds[array_rand($tagIds)];

                $translation->tags()->syncWithoutDetaching([$randomTagId]);
            }
        }
    }
}
