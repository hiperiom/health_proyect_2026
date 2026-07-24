<?php

namespace App\Models;

use Database\Factories\MedicalEspecialtiesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class MedicalEspecialties extends Model
{
    /** @use HasFactory<MedicalEspecialtiesFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [];
    }
}
