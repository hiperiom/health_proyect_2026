<?php
namespace App\Http\Controllers\MedicalSpecialties;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalSpecialty\StoreMedicalSpecialtyRequest;
use App\Http\Requests\MedicalSpecialty\UpdateMedicalSpecialtyRequest;
use App\Http\Resources\MedicalSpecialty\MedicalSpecialtyResource;
use App\Models\MedicalSpecialty;
use App\Services\MedicalSpecialty\MedicalSpecialtyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicalSpecialtiesController extends Controller {
    public function __construct(protected MedicalSpecialtyService $service) {}

    public function index(Request $request): Response {
        $items = $this->service->getList($request->query());
        return Inertia::render('medical_specialties/Index', [
            'items' => fn () => MedicalSpecialtyResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }
    public function store(StoreMedicalSpecialtyRequest $request): RedirectResponse {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Especialidades Médicas created.')]);
        return to_route('medical-specialties.index');
    }
    public function edit(Request $request, MedicalSpecialty $item): Response {
        return Inertia::render('medical_specialties/Index', [
            'item' => fn () => MedicalSpecialtyResource($item),
        ]);
    }
    public function update(UpdateMedicalSpecialtyRequest $request, MedicalSpecialty $item): RedirectResponse {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Especialidades Médicas updated.')]);
        return to_route('medical-specialties.index');
    }
    public function destroy(Request $request, MedicalSpecialty $item): RedirectResponse {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Especialidades Médicas deleted.')]);
        return to_route('medical-specialties.index');
    }
}