<?php

namespace App\Http\Controllers\MedicalEspecialties;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalEspecialties\StoreMedicalEspecialtiesRequest;
use App\Http\Requests\MedicalEspecialties\UpdateMedicalEspecialtiesRequest;
use App\Models\MedicalEspecialties;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicalEspecialtiesController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $items = MedicalEspecialties::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (MedicalEspecialties $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'createdAt' => $item->created_at?->toISOString(),
                'updatedAt' => $item->updated_at?->toISOString(),
            ]);

        return Inertia::render('medicalespecialties/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreMedicalEspecialtiesRequest $request): RedirectResponse
    {
        MedicalEspecialties::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Medical especialty created.')]);

        return to_route('medicalespecialties.index');
    }

    public function edit(Request $request, MedicalEspecialties $item): Response
    {
        return Inertia::render('medicalespecialties/Index', [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
            ],
        ]);
    }

    public function update(UpdateMedicalEspecialtiesRequest $request, MedicalEspecialties $item): RedirectResponse
    {
        $item->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Medical especialty updated.')]);

        return to_route('medicalespecialties.index');
    }

    public function destroy(Request $request, MedicalEspecialties $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Medical especialty deleted.')]);

        return to_route('medicalespecialties.index');
    }
}
