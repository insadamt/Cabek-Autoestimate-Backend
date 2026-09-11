<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::query()
            ->orderBy('libelle')
            ->get(['id', 'libelle'])
            ->map(fn (Source $source): array => [
                'id' => $source->id,
                'libelle' => $source->libelle,
            ]);

        return response()->json(['data' => $sources]);
    }
}
