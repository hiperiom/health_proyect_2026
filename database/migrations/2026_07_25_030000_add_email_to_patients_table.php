<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Email column removed from `patients`; do not re-add here.
    }

    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'email')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('email', 150)->after('phone_landline');
            });
        }
    }
};
