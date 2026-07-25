<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\PatientStatus;
use Database\Factories\PatientsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @use HasFactory<PatientsFactory>
 */
#[Fillable([
    'photo_path',
    'first_name',
    'last_name',
    'nacionality',
    'dni',
    'birth_date',
    'gender',
    'phone_mobile',
    'phone_landline',
    'created_by_user_id',
    'user_id',
    'status',
])]
class Patients extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'nacionality' => Nacionality::class,
            'gender' => Gender::class,
            'status' => PatientStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return Attribute<string|null, string|null> */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->photo_path) {
                return null;
            }

            return Storage::url($this->photo_path);
        });
    }
}
