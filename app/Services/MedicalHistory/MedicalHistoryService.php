<?php
namespace App\Services\MedicalHistory;
use App\Models\MedicalHistory;
use Illuminate\Pagination\LengthAwarePaginator;

class MedicalHistoryService {
    public function getList(array $filters): LengthAwarePaginator {
        $query = MedicalHistory::query();
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }
        $perPage = $filters['per_page'] ?? 10;
        return $query->latest()->paginate($perPage);
    }
    public function store(array $data): MedicalHistory { return MedicalHistory::create($data); }
    public function update(MedicalHistory $item, array $data): MedicalHistory { $item->update($data); return $item; }
    public function destroy(MedicalHistory $item): bool { return $item->delete(); }
}