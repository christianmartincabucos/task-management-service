<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        private readonly TranslationService $translationService,
    ) {}

    /**
     * Export translations as JSON for frontend consumption.
     *
     * Returns translations grouped by locale and key:
     * {
     *   "en": { "welcome_message": "Welcome!", ... },
     *   "fr": { "welcome_message": "Bienvenue!", ... }
     * }
     *
     * Optionally filter by locale: GET /api/export/en
     */
    public function __invoke(Request $request, ?string $locale = null): JsonResponse
    {
        $data = $this->translationService->export($locale);

        // If a specific locale was requested, return only that locale's translations
        if ($locale !== null && isset($data[$locale])) {
            $data = $data[$locale];
        }

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300')
            ->header('ETag', md5(json_encode($data)));
    }
}
