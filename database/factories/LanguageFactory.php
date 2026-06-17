<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $code = fake()->unique()->lexify('??');

        return [
            'code'      => $code,
            'name'      => 'Lang '.strtoupper($code),
            'is_active' => true,
        ];
    }
}
