<?php
namespace App\Services\Doctor;
use App\Models\Doctor;
use Illuminate\Pagination\LengthAwarePaginator;

class DoctorService {
    public function getList(array $filters): LengthAwarePaginator {
        $query = Doctor::query();
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }
        $perPage = $filters['per_page'] ?? 10;
        return $query->latest()->paginate($perPage);
    }
    public function store(array $data): Doctor { return Doctor::create($data); }
    public function update(Doctor $item, array $data): Doctor { $item->update($data); return $item; }
    public function destroy(Doctor $item): bool { return $item->delete(); }
}