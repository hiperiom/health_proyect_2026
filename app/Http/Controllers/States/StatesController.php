<?php

namespace App\Http\Controllers\States;

use App\Http\Controllers\Controller;
use App\Http\Requests\State\StoreStateRequest;
use App\Http\Requests\State\UpdateStateRequest;
use App\Http\Resources\State\StateResource;
use App\Models\State;
use App\Services\State\StateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatesController extends Controller
{
    public function __construct(protected StateService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('states/Index', [
            'items' => fn () => StateResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreStateRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado creado exitosamente.')]);

        return to_route('states.index');
    }

    public function edit(Request $request, State $item): Response
    {
        return Inertia::render('states/Index', [
            'item' => fn () => StateResource($item),
        ]);
    }

    public function update(UpdateStateRequest $request, State $item): RedirectResponse
    {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado actualizado exitosamente.')]);

        return to_route('states.index');
    }

    public function toggleActive(Request $request, State $item): RedirectResponse
    {
        $this->service->toggleActive($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado actualizado exitosamente.')]);

        return to_route('states.index');
    }

    public function destroy(Request $request, State $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Estado eliminado exitosamente.')]);

        return to_route('states.index');
    }
}
