<?php

namespace App\Http\Controllers\Patients;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\PatientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patients\StorePatientsRequest;
use App\Http\Requests\Patients\UpdatePatientsRequest;
use App\Models\Patients;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PatientsController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $nacionality = trim((string) $request->query('nacionality', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $items = Patients::query()
            ->with('createdBy:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('dni', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone_mobile', 'like', $like);
                });
            })
            ->when(in_array($status, PatientStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($nacionality, Nacionality::values(), true), fn ($query) => $query->where('nacionality', $nacionality))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Patients $item): array => [
                'id' => $item->id,
                'photoUrl' => $item->photo_url,
                'firstName' => $item->first_name,
                'lastName' => $item->last_name,
                'fullName' => trim($item->first_name.' '.$item->last_name),
                'nacionality' => $item->nacionality?->value,
                'nacionalityLabel' => $item->nacionality?->label(),
                'dni' => $item->dni,
                'birthDate' => $item->birth_date?->toDateString(),
                'gender' => $item->gender?->value,
                'genderLabel' => $item->gender?->label(),
                'phoneMobile' => $item->phone_mobile,
                'phoneLandline' => $item->phone_landline,
                'email' => $item->email,
                'createdByUserId' => $item->created_by_user_id,
                'createdByName' => $item->createdBy?->name,
                'status' => $item->status?->value,
                'statusLabel' => $item->status?->label(),
                'statusColorClass' => $item->status?->colorClass(),
                'createdAt' => $item->created_at?->toISOString(),
                'updatedAt' => $item->updated_at?->toISOString(),
            ]);

        return Inertia::render('patients/Index', [
            'items' => $items,
            'availableStatuses' => $this->availableStatuses(),
            'availableNacionalities' => $this->availableNacionalities(),
            'availableGenders' => $this->availableGenders(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'nacionality' => $nacionality,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StorePatientsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = Auth::id();

        DB::transaction(function () use ($data) {
            Patients::create($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient created.')]);

        return to_route('patients.index');
    }

    public function edit(Request $request, Patients $item): Response
    {
        $item->load('createdBy:id,name');

        return Inertia::render('patients/Index', [
            'item' => [
                'id' => $item->id,
                'photoUrl' => $item->photo_url,
                'firstName' => $item->first_name,
                'lastName' => $item->last_name,
                'nacionality' => $item->nacionality?->value,
                'dni' => $item->dni,
                'birthDate' => $item->birth_date?->toDateString(),
                'gender' => $item->gender?->value,
                'phoneMobile' => $item->phone_mobile,
                'phoneLandline' => $item->phone_landline,
                'email' => $item->email,
                'createdByUserId' => $item->created_by_user_id,
                'status' => $item->status?->value,
            ],
            'availableStatuses' => $this->availableStatuses(),
            'availableNacionalities' => $this->availableNacionalities(),
            'availableGenders' => $this->availableGenders(),
        ]);
    }

    public function update(UpdatePatientsRequest $request, Patients $item): RedirectResponse
    {
        DB::transaction(function () use ($item, $request) {
            $item->update($request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient updated.')]);

        return to_route('patients.index');
    }

    public function destroy(Request $request, Patients $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient deleted.')]);

        return to_route('patients.index');
    }

    /** @return array<int, array{value: string, label: string, colorClass: string}> */
    private function availableStatuses(): array
    {
        return collect(PatientStatus::cases())
            ->map(fn (PatientStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'colorClass' => $status->colorClass(),
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableNacionalities(): array
    {
        return collect(Nacionality::cases())
            ->map(fn (Nacionality $nacionality): array => [
                'value' => $nacionality->value,
                'label' => $nacionality->label(),
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableGenders(): array
    {
        return collect(Gender::cases())
            ->map(fn (Gender $gender): array => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])
            ->all();
    }
}
