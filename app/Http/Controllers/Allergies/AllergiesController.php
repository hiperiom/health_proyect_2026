<?php

namespace App\Http\Controllers\Allergies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Allergy\StoreAllergyRequest;
use App\Http\Requests\Allergy\UpdateAllergyRequest;
use App\Http\Resources\Allergy\AllergyResource;
use App\Models\Allergy;
use App\Services\Allergy\AllergyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllergiesController extends Controller
{
    public function __construct(protected AllergyService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('allergies/Index', [
            'items' => fn () => AllergyResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreAllergyRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alergia creada exitosamente.')]);

        return to_route('allergies.index');
    }

    public function edit(Request $request, Allergy $item): Response
    {
        return Inertia::render('allergies/Index', [
            'item' => fn () => AllergyResource($item),
        ]);
    }

    public function update(UpdateAllergyRequest $request, Allergy $item): RedirectResponse
    {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alergia actualizada exitosamente.')]);

        return to_route('allergies.index');
    }

    public function destroy(Request $request, Allergy $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alergia eliminada exitosamente.')]);

        return to_route('allergies.index');
    }
}
