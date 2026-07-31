<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'slug', 'module', 'description'])]
class Permission extends Model
{
    protected function casts(): array
    {
        return [];
    }

    /**
     * The module that this permission belongs to (matched by name on the
     * `module` column).
     *
     * @return BelongsTo<Module, Permission>
     */
    public function moduleRelation(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module', 'name');
    }
}
