<?php

namespace App\Http\Controllers\Doctors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctors\StoreDoctorRequest;
use App\Http\Requests\Doctors\UpdateDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Doctor::latest()->paginate();

        return Inertia::render('doctors/Index', [
            'items' => $items->through(fn (Doctor $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'createdAt' => $item->created_at->toISOString(),
                'updatedAt' => $item->updated_at->toISOString(),
            ]),
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $item = Doctor::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctor created.')]);

        return to_route('doctors.edit', ['doctors' => $item->id]);
    }

    public function edit(Request $request, Doctor $item): Response
    {
        return Inertia::render('doctors/Edit', [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
            ],
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $item): RedirectResponse
    {
        $item->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctor updated.')]);

        return to_route('doctors.edit', ['doctors' => $item->id]);
    }

    public function destroy(Request $request, Doctor $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Doctor deleted.')]);

        return to_route('doctors.index');
    }
}
