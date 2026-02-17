<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListOccurrencesRequest;
use App\Repositories\OccurrenceRepository;
use Illuminate\Http\JsonResponse;

class OccurrenceController extends Controller
{
    public function __construct(private OccurrenceRepository $occurrenceRepository) {}

    public function index(ListOccurrencesRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $occurrences = $this->occurrenceRepository->listByFilters(
            $filters['status'] ?? null,
            $filters['type'] ?? null
        );

        $data = $occurrences->map(function ($occurrence) {
            $occurrenceData = $occurrence->toArray();
            $occurrenceData['status'] = $occurrence->status->name();

            return $occurrenceData;
        });

        return response()->json([
            'data' => $data,
            'filters' => [
                'status' => $filters['status'] ?? null,
                'type' => $filters['type'] ?? null,
            ],
        ]);
    }
}
