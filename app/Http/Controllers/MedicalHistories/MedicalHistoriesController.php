<?php
namespace App\Http\Controllers\MedicalHistories;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalHistory\StoreMedicalHistoryRequest;
use App\Http\Requests\MedicalHistory\UpdateMedicalHistoryRequest;
use App\Http\Resources\MedicalHistory\MedicalHistoryResource;
use App\Models\MedicalHistory;
use App\Models\UsersProfile;
use App\Services\MedicalHistory\MedicalHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicalHistoriesController extends Controller {
    public function __construct(protected MedicalHistoryService $service) {}

    public function index(Request $request): Response {
        $items = $this->service->getList($request->query());
        return Inertia::render('medical_histories/Index', [
            'items' => fn () => MedicalHistoryResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }
    public function store(StoreMedicalHistoryRequest $request): RedirectResponse {
        $data = $request->validated();
        $patientProfile = UsersProfile::where('dni', $data['patient_identifier'])->first();

        if ($patientProfile !== null) {
            $data['patient_id'] = $patientProfile->user_id;

            if ($patientProfile->mrn && $patientProfile->mrn !== $data['mrn']) {
                return back()->withErrors(['mrn' => __('El MRN ingresado no coincide con el MRN del paciente.')])->withInput();
            }

            if (empty($patientProfile->mrn)) {
                $patientProfile->update(['mrn' => $data['mrn']]);
            }
        }

        $this->service->store($data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Historias Clínicas created.')]);
        return to_route('medical-histories.index');
    }
    public function edit(Request $request, MedicalHistory $item): Response {
        return Inertia::render('medical_histories/Index', [
            'item' => fn () => new MedicalHistoryResource($item),
        ]);
    }
    public function update(UpdateMedicalHistoryRequest $request, MedicalHistory $item): RedirectResponse {
        $data = $request->validated();
        $patientProfile = UsersProfile::where('dni', $data['patient_identifier'])->first();

        if ($patientProfile !== null) {
            $data['patient_id'] = $patientProfile->user_id;

            if ($patientProfile->mrn && $patientProfile->mrn !== $data['mrn']) {
                return back()->withErrors(['mrn' => __('El MRN ingresado no coincide con el MRN del paciente.')])->withInput();
            }

            if (empty($patientProfile->mrn)) {
                $patientProfile->update(['mrn' => $data['mrn']]);
            }
        }

        $this->service->update($item, $data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Historias Clínicas updated.')]);
        return to_route('medical-histories.index');
    }
    public function destroy(Request $request, MedicalHistory $item): RedirectResponse {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Historias Clínicas deleted.')]);
        return to_route('medical-histories.index');
    }
}