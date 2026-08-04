<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestStoreRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;

class ServiceRequestController extends Controller
{
    public function __construct(protected ServiceRequestService $service) {}

    public function store(ServiceRequestStoreRequest $request): JsonResponse
    {
        $serviceRequest = $this->service->create($request->validated());

        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
        ], 201);
    }
}
