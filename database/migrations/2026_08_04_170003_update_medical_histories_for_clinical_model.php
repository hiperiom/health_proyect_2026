<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('id')->constrained('users');
            $table->foreignId('encounter_id')->nullable()->after('patient_id')->constrained('encounters');
            $table->string('condition_code', 128)->nullable()->after('value');
            $table->string('condition_system', 128)->nullable();
            $table->string('condition_display', 255)->nullable();
            $table->timestampTz('onset_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->string('observation_code', 128)->nullable();
            $table->string('observation_system', 128)->nullable();
            $table->string('observation_display', 255)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('language', 35)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['encounter_id']);
            $table->dropColumn([
                'patient_id',
                'encounter_id',
                'condition_code',
                'condition_system',
                'condition_display',
                'onset_at',
                'resolved_at',
                'observation_code',
                'observation_system',
                'observation_display',
                'country',
                'language',
            ]);
        });
    }
};
