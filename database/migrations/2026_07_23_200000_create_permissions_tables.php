<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'permission_id']);
            $table->index('permission_id');
        });

        $defaults = [
            ['name' => 'View',        'slug' => 'users.read',        'module' => 'users',        'description' => 'View the users list and details'],
            ['name' => 'Create',      'slug' => 'users.create',     'module' => 'users',        'description' => 'Create new users'],
            ['name' => 'Update',      'slug' => 'users.update',     'module' => 'users',        'description' => 'Edit existing users'],
            ['name' => 'Delete',      'slug' => 'users.delete',     'module' => 'users',        'description' => 'Delete users'],
            ['name' => 'Assign Role', 'slug' => 'users.assignRole', 'module' => 'users',        'description' => 'Change a user role'],
            ['name' => 'Reset Password', 'slug' => 'users.resetPassword', 'module' => 'users',    'description' => 'Reset a user password'],
            ['name' => 'View',        'slug' => 'roles.read',       'module' => 'roles',        'description' => 'View the roles list and details'],
            ['name' => 'Create',      'slug' => 'roles.create',    'module' => 'roles',        'description' => 'Create new roles'],
            ['name' => 'Update',      'slug' => 'roles.update',    'module' => 'roles',        'description' => 'Edit existing roles'],
            ['name' => 'Delete',      'slug' => 'roles.delete',    'module' => 'roles',        'description' => 'Delete roles'],
            ['name' => 'Assign Permissions', 'slug' => 'roles.assignPermissions', 'module' => 'roles', 'description' => 'Assign permissions to a role'],
            ['name' => 'View',        'slug' => 'permissions.read',  'module' => 'permissions', 'description' => 'View the permissions list'],
            ['name' => 'Create',      'slug' => 'permissions.create', 'module' => 'permissions', 'description' => 'Create new permissions'],
            ['name' => 'Update',      'slug' => 'permissions.update', 'module' => 'permissions', 'description' => 'Edit existing permissions'],
            ['name' => 'Delete',      'slug' => 'permissions.delete', 'module' => 'permissions', 'description' => 'Delete permissions'],
        ];

        $now = now();

        foreach ($defaults as $row) {
            DB::table('permissions')->insert([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'module' => $row['module'],
                'description' => $row['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users_permissions');
        Schema::dropIfExists('permissions');
    }
};
