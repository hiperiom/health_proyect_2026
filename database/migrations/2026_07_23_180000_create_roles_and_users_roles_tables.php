<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{name: string, slug: string}> */
    private array $roles = [
        ['name' => 'Paciente', 'slug' => 'paciente'],
        ['name' => 'Doctor', 'slug' => 'doctor'],
        ['name' => 'Administrador', 'slug' => 'administrador'],
        ['name' => 'Superusuario', 'slug' => 'superusuario'],
        ['name' => 'Enfermería', 'slug' => 'enfermeria'],
        ['name' => 'Asistencial', 'slug' => 'asistencial'],
    ];

    /** @var array<string, string> */
    private array $legacyRoleMap = [
        'admin' => 'administrador',
        'user' => 'paciente',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('users_roles', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->unique('user_id');
            $table->index('role_id');
        });

        foreach ($this->roles as $role) {
            DB::table('roles')->insert([
                'name' => $role['name'],
                'slug' => $role['slug'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasColumn('users', 'role')) {
            $rolesBySlug = DB::table('roles')->pluck('id', 'slug');

            $legacyValues = array_keys($this->legacyRoleMap);

            $users = DB::table('users')
                ->whereIn('role', $legacyValues)
                ->select('id', 'role')
                ->get();

            $now = now();

            foreach ($users as $user) {
                $slug = $this->legacyRoleMap[$user->role] ?? 'paciente';

                if (! isset($rolesBySlug[$slug])) {
                    continue;
                }

                DB::table('users_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $rolesBySlug[$slug],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('user')->after('email');
            });
        }

        $rolesById = DB::table('roles')->pluck('slug', 'id');
        $legacyBySlug = array_flip($this->legacyRoleMap);

        $assignments = DB::table('users_roles')->get();

        foreach ($assignments as $assignment) {
            $slug = $rolesById[$assignment->role_id] ?? null;
            $legacy = $slug !== null ? ($legacyBySlug[$slug] ?? 'user') : 'user';

            DB::table('users')
                ->where('id', $assignment->user_id)
                ->update(['role' => $legacy]);
        }

        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('roles');
    }
};
