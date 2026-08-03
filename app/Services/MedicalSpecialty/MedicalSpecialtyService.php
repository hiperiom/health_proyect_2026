<?php

namespace App\Services\MedicalSpecialty;

use App\Models\MedicalSpecialty;
use Illuminate\Pagination\LengthAwarePaginator;

class MedicalSpecialtyService
{
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = MedicalSpecialty::query();
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }
        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data): MedicalSpecialty
    {
        return MedicalSpecialty::create($data);
    }

    public function update(MedicalSpecialty $item, array $data): MedicalSpecialty
    {
        $item->update($data);

        return $item;
    }

    public function destroy(MedicalSpecialty $item): bool
    {
        return $item->delete();
    }
}
