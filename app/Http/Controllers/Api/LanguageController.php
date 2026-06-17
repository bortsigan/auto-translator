<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::query()->orderBy('code')->get(['id', 'code', 'name', 'is_active']);

        return response()->json(['data' => $languages]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $language = Language::query()->create($data);

        return response()->json(['data' => $language], 201);
    }
}
