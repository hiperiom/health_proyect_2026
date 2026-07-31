<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivote: qué módulos están asociados a cada rol
        Schema::create('roles_modules', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'module_id']);
            $table->index('module_id');
        });

        // Pivote: qué permisos (de los disponibles en cada módulo) están
        // habilitados para cada combinación rol/módulo. Combinación única.
        Schema::create('roles_modules_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'module_id', 'permission_id']);
            $table->index(['role_id', 'module_id']);
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_modules_permissions');
        Schema::dropIfExists('roles_modules');
    }
};
