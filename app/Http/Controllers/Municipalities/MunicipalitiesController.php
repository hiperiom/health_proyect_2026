<?php

namespace App\Http\Controllers\Municipalities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Municipality\StoreMunicipalityRequest;
use App\Http\Requests\Municipality\UpdateMunicipalityRequest;
use App\Http\Resources\Municipality\MunicipalityResource;
use App\Models\Municipality;
use App\Services\Municipality\MunicipalityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipalitiesController extends Controller
{
    public function __construct(protected MunicipalityService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('municipalities/Index', [
            'items' => fn () => MunicipalityResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreMunicipalityRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio creado exitosamente.')]);

        return to_route('municipalities.index');
    }

    public function edit(Request $request, Municipality $item): Response
    {
        return Inertia::render('municipalities/Index', [
            'item' => fn () => MunicipalityResource($item),
        ]);
    }

    public function update(UpdateMunicipalityRequest $request, Municipality $item): RedirectResponse
    {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio actualizado exitosamente.')]);

        return to_route('municipalities.index');
    }

    public function toggleActive(Request $request, Municipality $item): RedirectResponse
    {
        $this->service->toggleActive($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio actualizado exitosamente.')]);

        return to_route('municipalities.index');
    }

    public function destroy(Request $request, Municipality $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Municipio eliminado exitosamente.')]);

        return to_route('municipalities.index');
    }
}
