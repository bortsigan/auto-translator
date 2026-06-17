<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    public function search(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 200));

        $query = Translation::query()
            ->select(['translations.id', 'translations.language_id', 'translations.key', 'translations.content', 'translations.updated_at'])
            ->with(['language:id,code,name', 'tags:id,name']);

        if (! empty($filters['locale'])) {
            $query->whereHas('language', fn (Builder $q) => $q->where('code', $filters['locale']));
        }

        if (! empty($filters['key'])) {
            $query->where('translations.key', 'like', $filters['key'].'%');
        }

        if (! empty($filters['content'])) {
            $this->applyContentFilter($query, (string) $filters['content']);
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('name', $filters['tag']));
        }

        return $query->orderBy('translations.id')->paginate($perPage);
    }

    public function create(array $data): Translation
    {
        return DB::transaction(function () use ($data): Translation {
            $translation = Translation::query()->create([
                'language_id' => $data['language_id'],
                'key'         => $data['key'],
                'content'     => $data['content'],
            ]);

            if (! empty($data['tags'])) {
                $translation->tags()->sync($this->resolveTagIds($data['tags']));
            }

            $translation->load(['language:id,code,name', 'tags:id,name']);

            return $translation;
        });
    }

    public function update(Translation $translation, array $data): Translation
    {
        return DB::transaction(function () use ($translation, $data): Translation {
            $translation->fill(array_intersect_key($data, array_flip(['language_id', 'key', 'content'])));
            $translation->save();

            if (array_key_exists('tags', $data)) {
                $translation->tags()->sync($this->resolveTagIds($data['tags'] ?? []));
            }

            $translation->load(['language:id,code,name', 'tags:id,name']);

            return $translation;
        });
    }

    public function delete(Translation $translation): void
    {
        $translation->delete();
    }

    private function resolveTagIds(array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        if ($names === []) {
            return [];
        }

        Tag::query()->upsert(
            array_map(fn (string $n): array => ['name' => $n], $names),
            ['name'],
            ['name'],
        );

        return Tag::query()->whereIn('name', $names)->pluck('id')->all();
    }

    private function applyContentFilter(Builder $query, string $content): void
    {
        if (mb_strlen($content) >= 3) {
            $query->whereRaw(
                'MATCH(translations.content) AGAINST (? IN BOOLEAN MODE)',
                [$this->buildBooleanQuery($content)],
            );
            return;
        }

        $query->where('translations.content', 'like', '%'.$content.'%');
    }

    private function buildBooleanQuery(string $term): string
    {
        $sanitized = preg_replace('/[+\-><\(\)~*"@]+/', ' ', $term) ?? '';
        $tokens = array_filter(array_map('trim', explode(' ', $sanitized)));
        $tokens = array_map(static fn (string $t): string => '+'.$t.'*', $tokens);

        return implode(' ', $tokens);
    }
}
