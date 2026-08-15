<?php

namespace App\Http\Controllers\HealthBackgrounds;

use App\Http\Controllers\Controller;
use App\Http\Requests\HealthBackground\StoreHealthBackgroundRequest;
use App\Http\Requests\HealthBackground\UpdateHealthBackgroundRequest;
use App\Http\Resources\HealthBackground\HealthBackgroundResource;
use App\Models\HealthBackground;
use App\Services\HealthBackground\HealthBackgroundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthBackgroundsController extends Controller
{
    public function __construct(protected HealthBackgroundService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('health_backgrounds/Index', [
            'items' => fn () => HealthBackgroundResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreHealthBackgroundRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Antecedente de salud creado exitosamente.')]);

        return to_route('health-backgrounds.index');
    }

    public function edit(Request $request, HealthBackground $item): Response
    {
        return Inertia::render('health_backgrounds/Index', [
            'item' => fn () => HealthBackgroundResource($item),
        ]);
    }

    public function update(UpdateHealthBackgroundRequest $request, HealthBackground $item): RedirectResponse
    {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Antecedente de salud actualizado exitosamente.')]);

        return to_route('health-backgrounds.index');
    }

    public function destroy(Request $request, HealthBackground $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Antecedente de salud eliminado exitosamente.')]);

        return to_route('health-backgrounds.index');
    }
}
