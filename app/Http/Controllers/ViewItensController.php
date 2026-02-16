<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListOccurrencesRequest;
use App\Repositories\OccurrenceRepository;
use Illuminate\Http\JsonResponse;

class ViewItensController extends Controller
{
    public function __construct(private OccurrenceRepository $occurrenceRepository) {}

    public function index(ListOccurrencesRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $occurrences = $this->occurrenceRepository->listByFilters(
            $filters['status'] ?? null,
            $filters['type'] ?? null
        );

        return response()->json([
            'data' => $occurrences,
            'filters' => [
                'status' => $filters['status'] ?? null,
                'type' => $filters['type'] ?? null,
            ],
        ]);
    }
}
