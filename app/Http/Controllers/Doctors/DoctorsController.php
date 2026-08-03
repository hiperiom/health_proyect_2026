<?php

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\Doctor\DoctorResource;
use App\Models\Doctor;
use App\Services\Doctor\DoctorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorsController extends Controller
{
    public function __construct(protected DoctorService $service) {}

    public function index(Request $request): Response
    {
        $items = $this->service->getList($request->query());

        return Inertia::render('doctors/Index', [
            'items' => fn () => DoctorResource::collection($items),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctores created.')]);

        return to_route('doctors.index');
    }

    public function edit(Request $request, Doctor $item): Response
    {
        return Inertia::render('doctors/Index', [
            'item' => fn () => DoctorResource($item),
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $item): RedirectResponse
    {
        $this->service->update($item, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctores updated.')]);

        return to_route('doctors.index');
    }

    public function destroy(Request $request, Doctor $item): RedirectResponse
    {
        $this->service->destroy($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctores deleted.')]);

        return to_route('doctors.index');
    }
}
