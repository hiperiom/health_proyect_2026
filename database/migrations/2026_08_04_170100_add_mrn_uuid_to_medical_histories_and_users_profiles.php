<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('users_profiles', 'mrn')) {
                $table->string('mrn', 50)->nullable()->unique()->after('dni');
            }
        });

        Schema::table('medical_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_histories', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('medical_histories', 'patient_identifier')) {
                $table->string('patient_identifier', 50)->nullable()->after('encounter_id');
            }
            if (! Schema::hasColumn('medical_histories', 'mrn')) {
                $table->string('mrn', 50)->nullable()->unique()->after('patient_identifier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            if (Schema::hasColumn('medical_histories', 'mrn')) {
                $table->dropUnique(['mrn']);
                $table->dropColumn('mrn');
            }
            if (Schema::hasColumn('medical_histories', 'patient_identifier')) {
                $table->dropColumn('patient_identifier');
            }
            if (Schema::hasColumn('medical_histories', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });

        Schema::table('users_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('users_profiles', 'mrn')) {
                $table->dropUnique(['mrn']);
                $table->dropColumn('mrn');
            }
        });
    }
};
