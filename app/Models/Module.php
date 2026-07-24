<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class Module extends Model
{
    protected function casts(): array
    {
        return [];
    }
}
