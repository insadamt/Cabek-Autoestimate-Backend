<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Marque;
use Illuminate\Http\JsonResponse;

class MarqueController extends Controller
{
    public function index(): JsonResponse
    {
        $marques = Marque::query()
            ->orderBy('libelle')
            ->get(['id', 'libelle'])
            ->map(fn (Marque $marque): array => [
                'id' => $marque->id,
                'libelle' => $marque->libelle,
            ]);

        return response()->json(['data' => $marques]);
    }
}
