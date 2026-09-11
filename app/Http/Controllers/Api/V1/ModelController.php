<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ModelIndexRequest;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;

class ModelController extends Controller
{
    public function index(ModelIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = VehicleModel::query()
            ->orderBy('libelle')
            ->select(['id', 'marque_id', 'libelle']);

        if (isset($filters['marque_id'])) {
            $models = $query
                ->where('marque_id', $filters['marque_id'])
                ->get()
                ->map(fn (VehicleModel $model): array => $this->serialize($model));

            return response()->json(['data' => $models]);
        }

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 50));

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (VehicleModel $model): array => $this->serialize($model))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function serialize(VehicleModel $model): array
    {
        return [
            'id' => $model->id,
            'marque_id' => $model->marque_id,
            'libelle' => $model->libelle,
        ];
    }
}
