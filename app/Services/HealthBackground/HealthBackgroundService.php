<?php

namespace App\Services\HealthBackground;

use App\Models\HealthBackground;
use Illuminate\Pagination\LengthAwarePaginator;

class HealthBackgroundService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = HealthBackground::query();
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): HealthBackground
    {
        return HealthBackground::create($data);
    }

    public function update(HealthBackground $item, array $data): HealthBackground
    {
        $item->update($data);

        return $item;
    }

    public function destroy(HealthBackground $item): bool
    {
        return $item->delete();
    }
}
