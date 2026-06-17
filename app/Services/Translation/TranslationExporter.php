<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationExporter
{
    public function export(string $localeCode, array $tags = []): StreamedResponse
    {
        /** @var Language|null $language */
        $language = Language::query()->where('code', $localeCode)->first(['id', 'code']);

        if ($language === null) {
            return $this->emptyResponse($localeCode);
        }

        [$etag, $lastModified] = $this->freshnessHeaders($language->id, $tags);

        $request = request();
        $ifNoneMatch = $request->headers->get('If-None-Match');

        if ($ifNoneMatch !== null && trim($ifNoneMatch, '"') === trim($etag, '"')) {
            return new StreamedResponse(static fn () => null, 304, $this->cacheHeaders($etag, $lastModified));
        }

        $headers = array_merge($this->cacheHeaders($etag, $lastModified), [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);

        return new StreamedResponse(function () use ($language, $tags): void {
            $this->streamJson($language->id, $tags);
        }, 200, $headers);
    }

    private function freshnessHeaders(int $languageId, array $tags): array
    {
        $query = DB::table('translations')
            ->where('language_id', $languageId);

        if ($tags !== []) {
            $query->whereExists(function ($q) use ($tags): void {
                $q->select(DB::raw(1))
                    ->from('tag_translation')
                    ->join('tags', 'tags.id', '=', 'tag_translation.tag_id')
                    ->whereColumn('tag_translation.translation_id', 'translations.id')
                    ->whereIn('tags.name', $tags);
            });
        }

        /** @var object{max_updated: string|null, total: int}|null $row */
        $row = $query->selectRaw('MAX(updated_at) AS max_updated, COUNT(*) AS total')->first();

        $max = $row?->max_updated;
        $count = (int) ($row?->total ?? 0);
        $stamp = $max !== null ? Carbon::parse($max)->getTimestamp() : 0;

        $etag = '"'.sha1($languageId.':'.implode(',', $tags).':'.$stamp.':'.$count).'"';
        $lastModified = Carbon::createFromTimestamp(max($stamp, 0))->toRfc7231String();

        return [$etag, $lastModified];
    }

    private function cacheHeaders(string $etag, string $lastModified): array
    {
        return [
            'ETag'          => $etag,
            'Last-Modified' => $lastModified,
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'Vary'          => 'Accept, Accept-Encoding, Authorization',
        ];
    }

    private function emptyResponse(string $localeCode): StreamedResponse
    {
        $etag = '"'.sha1($localeCode.':empty').'"';

        return new StreamedResponse(function (): void {
            echo '{}';
        }, 200, $this->cacheHeaders($etag, Carbon::createFromTimestamp(0)->toRfc7231String()) + [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    private function streamJson(int $languageId, array $tags): void
    {
        echo '{';
        $first = true;

        $query = DB::table('translations')
            ->where('language_id', $languageId)
            ->select(['translations.id', 'translations.key', 'translations.content']);

        if ($tags !== []) {
            $query->whereExists(function ($q) use ($tags): void {
                $q->select(DB::raw(1))
                    ->from('tag_translation')
                    ->join('tags', 'tags.id', '=', 'tag_translation.tag_id')
                    ->whereColumn('tag_translation.translation_id', 'translations.id')
                    ->whereIn('tags.name', $tags);
            });
        }

        $query->orderBy('translations.id')->chunkById(5000, function ($rows) use (&$first): void {
            $buffer = '';
            foreach ($rows as $row) {
                $piece = json_encode($row->key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    .':'
                    .json_encode($row->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $buffer .= ($first ? '' : ',').$piece;
                $first = false;
            }
            echo $buffer;
            if (function_exists('ob_get_level') && ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
        }, 'translations.id', 'id');

        echo '}';
    }
}
