<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Translation
 */
class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'key'        => $this->key,
            'content'    => $this->content,
            'language'   => $this->whenLoaded('language', fn () => [
                'id'   => $this->language->id,
                'code' => $this->language->code,
                'name' => $this->language->name,
            ]),
            'tags'       => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->all()),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
