<?php

namespace App\Services\Allergy;

use App\Models\Allergy;
use Illuminate\Pagination\LengthAwarePaginator;

class AllergyService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = Allergy::query();
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): Allergy
    {
        return Allergy::create($data);
    }

    public function update(Allergy $item, array $data): Allergy
    {
        $item->update($data);

        return $item;
    }

    public function destroy(Allergy $item): bool
    {
        return $item->delete();
    }
}
