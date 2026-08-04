<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'patient_id',
        'patient_identifier',
        'mrn',
        'encounter_id',
        'name',
        'description',
        'value',
        'condition_code',
        'condition_system',
        'condition_display',
        'observation_code',
        'observation_system',
        'observation_display',
        'onset_at',
        'resolved_at',
        'country',
        'language',
    ];

    protected $casts = [
        'onset_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $medicalHistory): void {
            if (empty($medicalHistory->uuid)) {
                $medicalHistory->uuid = (string) Str::ulid();
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class, 'encounter_id');
    }
}