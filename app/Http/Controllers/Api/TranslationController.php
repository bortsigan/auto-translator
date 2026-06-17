<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\SearchTranslationRequest;
use App\Http\Requests\Translation\StoreTranslationRequest;
use App\Http\Requests\Translation\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\Translation\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function __construct(private readonly TranslationService $service)
    {
    }

    public function index(SearchTranslationRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $paginator = $this->service->search($filters);

        return TranslationResource::collection($paginator);
    }

    public function show(Translation $translation): TranslationResource
    {
        $translation->load(['language:id,code,name', 'tags:id,name']);

        return new TranslationResource($translation);
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $translation = $this->service->create($data);

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): TranslationResource
    {
        $data = $request->validated();
        $translation = $this->service->update($translation, $data);

        return new TranslationResource($translation);
    }

    public function destroy(Translation $translation): JsonResponse
    {
        $this->service->delete($translation);

        return response()->json(null, 204);
    }
}
