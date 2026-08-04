<?php

namespace App\Services\State;

use App\Models\State;
use Illuminate\Pagination\LengthAwarePaginator;

class StateService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = State::query();
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): State
    {
        return State::create($data);
    }

    public function update(State $item, array $data): State
    {
        $item->update($data);

        return $item;
    }

    public function destroy(State $item): bool
    {
        return $item->delete();
    }

    public function toggleActive(State $item): State
    {
        $item->update(['is_active' => ! $item->is_active]);

        return $item;
    }
}
