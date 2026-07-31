<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'display_name', 'description'])]
class Module extends Model
{
    protected function casts(): array
    {
        return [];
    }

    /**
     * Permissions that belong to this module.
     *
     * @return HasMany<Permission>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'module', 'name');
    }

    /**
     * Roles that have access to this module.
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_modules')
            ->withTimestamps();
    }
}
