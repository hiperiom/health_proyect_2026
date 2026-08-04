<?php

namespace App\Http\Controllers;

use App\Http\Requests\EncounterStoreRequest;
use App\Http\Resources\EncounterResource;
use App\Models\Encounter;
use App\Services\EncounterService;
use Illuminate\Http\JsonResponse;

class EncounterController extends Controller
{
    public function __construct(protected EncounterService $service) {}

    public function store(EncounterStoreRequest $request): JsonResponse
    {
        $encounter = $this->service->create($request->validated());

        return response()->json([
            'data' => new EncounterResource($encounter),
        ], 201);
    }
}
