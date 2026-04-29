<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $translationService,
    ) {}

    /**
     * List translations with optional filters.
     *
     * Supports filtering by: locale, key, tag, search (partial match on key/content).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['locale', 'key', 'tag', 'search']);
        $perPage = (int) $request->get('per_page', 20);

        $translations = $this->translationService->list($filters, min($perPage, 100));

        return TranslationResource::collection($translations);
    }

    /**
     * Create a new translation.
     */
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->translationService->create($request->validated());

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * View a single translation.
     */
    public function show(int $id): TranslationResource
    {
        $translation = $this->translationService->find($id);

        return new TranslationResource($translation);
    }

    /**
     * Update a translation.
     */
    public function update(UpdateTranslationRequest $request, int $id): TranslationResource
    {
        $translation = $this->translationService->update($id, $request->validated());

        return new TranslationResource($translation);
    }

    /**
     * Delete a translation.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->translationService->delete($id);

        return response()->json([
            'message' => 'Translation deleted successfully.',
        ]);
    }
}
