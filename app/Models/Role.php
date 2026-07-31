<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'slug', 'color_class', 'text_class', 'icon_svg'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /** Slug of the "superuser" role used in seeders and helpers. */
    public const SUPERUSER_SLUG = 'superusuario';

    /**
     * Get the users that belong to this role.
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles')
            ->withTimestamps();
    }

    /**
     * Modules associated to this role.
     *
     * @return BelongsToMany<Module>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'roles_modules')
            ->withTimestamps();
    }

    /**
     * Permissions that are enabled for this role on the given modules.
     * Returned as a relation so callers can constrain the module id.
     *
     * @return BelongsToMany<Permission>
     */
    public function modulePermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'roles_modules_permissions',
            'role_id',
            'permission_id'
        )
            ->withPivot('module_id')
            ->withTimestamps();
    }

    /**
     * Convenience: return the permission ids that are currently enabled
     * for this role on the given module.
     *
     * @return list<int>
     */
    public function enabledPermissionIdsForModule(int $moduleId): array
    {
        return DB::table('roles_modules_permissions')
            ->where('role_id', $this->id)
            ->where('module_id', $moduleId)
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
