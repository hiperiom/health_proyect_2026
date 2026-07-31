<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Default associations for the `superusuario` role.
 *
 * This migration:
 *   1. Attaches every row in `modules` to the `superusuario` role
 *      via the `roles_modules` pivot table.
 *   2. Attaches every permission of every module to the
 *      `superusuario` role via the `roles_modules_permissions` table,
 *      so the role has every CRUD permission on every module.
 *
 * It is idempotent: re-running it will not produce duplicate rows.
 */
return new class extends Migration
{
    private const SUPERUSER_SLUG = 'superusuario';

    public function up(): void
    {
        // Guard: if any of the required tables does not exist yet
        // (e.g. when running migrations out of order), skip silently
        // instead of throwing.
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('modules')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles_modules')
            || ! Schema::hasTable('roles_modules_permissions')) {
            return;
        }

        $superuser = DB::table('roles')->where('slug', self::SUPERUSER_SLUG)->first();

        if ($superuser === null) {
            return;
        }

        $now = now();

        // 1) Attach every module to the superusuario role.
        $modules = DB::table('modules')->select('id')->get();
        foreach ($modules as $module) {
            $exists = DB::table('roles_modules')
                ->where('role_id', $superuser->id)
                ->where('module_id', $module->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('roles_modules')->insert([
                'role_id' => $superuser->id,
                'module_id' => $module->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2) Attach every permission of every module to the superusuario role.
        $permissions = DB::table('permissions')->select('id', 'module')->get();
        $moduleIdsByName = DB::table('modules')
            ->pluck('id', 'name')
            ->all();

        foreach ($permissions as $permission) {
            $moduleId = $moduleIdsByName[$permission->module] ?? null;

            if ($moduleId === null) {
                // Permission references a module that does not exist;
                // skip it so the migration does not violate the FK.
                continue;
            }

            $exists = DB::table('roles_modules_permissions')
                ->where('role_id', $superuser->id)
                ->where('module_id', $moduleId)
                ->where('permission_id', $permission->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('roles_modules_permissions')->insert([
                'role_id' => $superuser->id,
                'module_id' => $moduleId,
                'permission_id' => $permission->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $superuser = DB::table('roles')->where('slug', self::SUPERUSER_SLUG)->first();

        if ($superuser === null) {
            return;
        }

        if (Schema::hasTable('roles_modules')) {
            DB::table('roles_modules')
                ->where('role_id', $superuser->id)
                ->delete();
        }

        if (Schema::hasTable('roles_modules_permissions')) {
            DB::table('roles_modules_permissions')
                ->where('role_id', $superuser->id)
                ->delete();
        }
    }
};
