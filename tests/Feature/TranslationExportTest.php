<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use DB;

class TranslationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_flat_key_to_content_map(): void
    {
        $en = Language::factory()->create(['code' => 'en']);
        Translation::factory()->create(['language_id' => $en->id, 'key' => 'a',     'content' => 'A']);
        Translation::factory()->create(['language_id' => $en->id, 'key' => 'b.c',   'content' => 'BC']);

        $response = $this->get('/api/translations/export/en');
        $response->assertOk();

        $body = $response->streamedContent();
        $data = json_decode($body, true);
        $this->assertSame(['a' => 'A', 'b.c' => 'BC'], $data);
    }

    public function test_export_returns_empty_object_for_unknown_locale(): void
    {
        $response = $this->get('/api/translations/export/zz');
        $response->assertOk();
        $this->assertSame('{}', trim($response->streamedContent()));
    }

    public function test_export_handles_large_dataset_under_500ms(): void
    {
        $en = Language::factory()->create(['code' => 'en']);
        $rows = [];
        $now = now();
        for ($i = 0; $i < 5000; $i++) {
            $rows[] = [
                'language_id' => $en->id,
                'key'         => 'perf.key.'.$i,
                'content'     => 'value '.$i,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('translations')->insert($chunk);
        }

        $start = microtime(true);
        $response = $this->get('/api/translations/export/en');
        $response->streamedContent();
        $elapsedMs = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(
            500,
            $elapsedMs,
            "Export took {$elapsedMs}ms, expected <500ms",
        );
    }
}
