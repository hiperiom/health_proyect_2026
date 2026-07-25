<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El único email que debe existir para un paciente es el email
     * de su cuenta de usuario (`users.email`). Por lo tanto, se
     * elimina la columna `email` de la tabla `patients`.
     */
    public function up(): void
    {
        if (Schema::hasColumn('patients', 'email')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'email')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('email', 150);
            });
        }
    }
};
