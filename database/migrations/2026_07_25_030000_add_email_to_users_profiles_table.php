<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Email column removed from `users_profiles`; do not re-add here.
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users_profiles', 'email')) {
            Schema::table('users_profiles', function (Blueprint $table) {
                $table->string('email', 150)->after('phone_landline');
            });
        }
    }
};
