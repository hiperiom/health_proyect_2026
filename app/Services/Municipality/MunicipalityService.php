<?php

namespace App\Services\Municipality;

use App\Models\Municipality;
use Illuminate\Pagination\LengthAwarePaginator;

class MunicipalityService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = Municipality::query()->with('state');
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): Municipality
    {
        return Municipality::create($data);
    }

    public function update(Municipality $item, array $data): Municipality
    {
        $item->update($data);

        return $item;
    }

    public function destroy(Municipality $item): bool
    {
        return $item->delete();
    }

    public function toggleActive(Municipality $item): Municipality
    {
        $item->update(['is_active' => ! $item->is_active]);

        return $item;
    }
}
