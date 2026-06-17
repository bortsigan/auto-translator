<?php

declare(strict_types=1);

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language_id' => ['sometimes', 'integer', 'exists:languages,id'],
            'key'         => ['sometimes', 'string', 'max:191'],
            'content'     => ['sometimes', 'string'],
            'tags'        => ['sometimes', 'array'],
            'tags.*'      => ['string', 'max:64'],
        ];
    }
}
