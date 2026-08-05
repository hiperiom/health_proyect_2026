<?php

namespace App\Http\Controllers\MedicalHistories;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalHistory\SearchPatientRequest;
use App\Http\Requests\MedicalHistory\StoreMedicalHistoryRequest;
use App\Http\Requests\MedicalHistory\UpdateMedicalHistoryRequest;
use App\Http\Resources\MedicalHistory\MedicalHistoryResource;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use App\Models\UsersProfile;
use App\Services\MedicalHistory\MedicalHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MedicalHistoriesController extends Controller
{
    public function __construct(protected MedicalHistoryService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('medical_histories/Index', [
            'items' => fn () => MedicalHistoryResource::collection($items),
            'availableStatuses' => $this->availableStatuses(),
            'availableNacionalities' => $this->availableNacionalities(),
            'availableGenders' => $this->availableGenders(),
            'availableStates' => $this->availableStates(),
            'availableMunicipalities' => $this->availableMunicipalities(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    private function availableNacionalities(): array
    {
        return collect(Nacionality::cases())
            ->map(fn (Nacionality $nacionality): array => [
                'value' => $nacionality->value,
                'label' => $nacionality->label(),
            ])
            ->all();
    }

    private function availableGenders(): array
    {
        return collect(Gender::cases())
            ->map(fn (Gender $gender): array => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])
            ->all();
    }

    private function availableStatuses(): array
    {
        return collect(UserStatus::cases())
            ->map(fn (UserStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'colorClass' => $status->colorClass(),
            ])
            ->all();
    }

    private function availableStates(): array
    {
        return State::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (State $state): array => [
                'id' => $state->id,
                'name' => $state->name,
            ])
            ->all();
    }

    private function availableMunicipalities(): array
    {
        return Municipality::query()
            ->orderBy('name')
            ->get(['id', 'name', 'state_id'])
            ->map(fn (Municipality $municipality): array => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'state_id' => $municipality->state_id,
            ])
            ->all();
    }

    public function search(SearchPatientRequest $request): JsonResponse
    {
        $q = trim((string) $request->validated('q'));

        $mrnPattern = '/^[A-Z0-9]{2,10}-[0-9]{4}-[0-9]{3,10}-[0-9]$/';

        if (preg_match($mrnPattern, $q)) {
            $history = MedicalHistory::query()
                ->where('mrn', $q)
                ->with('patient')
                ->first();

            if ($history) {
                $profile = $history->patient->usersProfile->first();

                return response()->json([
                    'found' => true,
                    'hasHistory' => true,
                    'ticket' => [
                        'mrn' => $history->mrn,
                        'firstName' => $profile?->first_name,
                        'lastName' => $profile?->last_name,
                        'dni' => $profile?->dni,
                        'totalEncounters' => Encounter::where('patient_id', $history->patient_id)->count(),
                    ],
                ]);
            }

            return response()->json([
                'found' => true,
                'hasHistory' => false,
            ]);
        }

        $user = User::query()
            ->where('email', $q)
            ->with('usersProfile')
            ->first();

        if ($user) {
            $profile = $user->usersProfile->first();
            $hasHistory = MedicalHistory::query()
                ->where('patient_id', $user->id)
                ->orWhere('patient_identifier', $profile?->dni)
                ->exists();

            if ($hasHistory) {
                $history = MedicalHistory::query()
                    ->where('patient_id', $user->id)
                    ->orWhere('patient_identifier', $profile?->dni)
                    ->first();

                return response()->json([
                    'found' => true,
                    'hasHistory' => true,
                    'ticket' => [
                        'mrn' => $history?->mrn,
                        'firstName' => $profile?->first_name,
                        'lastName' => $profile?->last_name,
                        'dni' => $profile?->dni,
                        'totalEncounters' => Encounter::where('patient_id', $user->id)->count(),
                    ],
                ]);
            }

            return response()->json([
                'found' => true,
                'hasHistory' => false,
                'patient' => [
                    'userId' => $user->id,
                    'dni' => $profile?->dni,
                    'email' => $user->email,
                ],
            ]);
        }

        $profile = UsersProfile::query()
            ->where('dni', $q)
            ->first();

        if ($profile) {
            $hasHistory = MedicalHistory::query()
                ->where('patient_id', $profile->user_id)
                ->orWhere('patient_identifier', $profile->dni)
                ->exists();

            if ($hasHistory) {
                $history = MedicalHistory::query()
                    ->where('patient_id', $profile->user_id)
                    ->orWhere('patient_identifier', $profile->dni)
                    ->first();

                return response()->json([
                    'found' => true,
                    'hasHistory' => true,
                    'ticket' => [
                        'mrn' => $history?->mrn,
                        'firstName' => $profile->first_name,
                        'lastName' => $profile->last_name,
                        'dni' => $profile->dni,
                        'totalEncounters' => Encounter::where('patient_id', $profile->user_id)->count(),
                    ],
                ]);
            }

            return response()->json([
                'found' => true,
                'hasHistory' => false,
                'patient' => [
                    'userId' => $profile->user_id,
                    'dni' => $profile->dni,
                    'email' => $profile->user->email ?? null,
                ],
            ]);
        }

        return response()->json([
            'found' => false,
            'hasHistory' => false,
        ]);
    }

    public function store(StoreMedicalHistoryRequest $request): RedirectResponse
    {
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
        } else {
            $temporaryPassword = Str::password(16);

            $patientId = null;

            DB::transaction(function () use ($data, $temporaryPassword, &$patientId) {
                $user = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'email' => $data['email'],
                    'status' => $data['status'],
                    'password' => $temporaryPassword,
                    'password_updated' => false,
                ]);

                $role = Role::query()->firstOrCreate(
                    ['slug' => 'paciente'],
                    ['name' => 'Paciente']
                );
                $user->roles()->sync([$role->id]);

                UsersProfile::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'nacionality' => $data['nacionality'],
                    'dni' => $data['dni'],
                    'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'],
                    'phone_mobile' => $data['phone_mobile'],
                    'phone_landline' => $data['phone_landline'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'municipality_id' => $data['municipality_id'] ?? null,
                    'address' => $data['address'] ?? null,
                    'mrn' => $data['mrn'],
                    'user_id' => $user->id,
                    'created_by_user_id' => auth()->id(),
                ]);

                $patientId = $user->id;
            });

            $data['patient_id'] = $patientId;
        }

        if (empty($data['name'])) {
            if ($patientProfile !== null) {
                $data['name'] = trim($patientProfile->first_name.' '.$patientProfile->last_name);
            } else {
                $data['name'] = trim($data['first_name'].' '.$data['last_name']);
            }
        }

        $this->service->store($data);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Historias Clínicas created.')]);

        return to_route('medical-histories.index');
    }

    public function edit(Request $request, MedicalHistory $item): Response
    {
        return Inertia::render('medical_histories/Index', [
            'item' => fn () => new MedicalHistoryResource($item),
        ]);
    }

    public function update(UpdateMedicalHistoryRequest $request, MedicalHistory $item): RedirectResponse
    {
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

    public function destroy(Request $request, MedicalHistory $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Historias Clínicas deleted.')]);

        return to_route('medical-histories.index');
    }
}
