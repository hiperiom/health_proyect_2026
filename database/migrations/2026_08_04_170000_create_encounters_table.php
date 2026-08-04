<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users');
            $table->string('encounter_class', 3);
            $table->string('status', 32);
            $table->timestampTz('start_at');
            $table->timestampTz('end_at')->nullable();
            $table->string('reason_code', 128)->nullable();
            $table->string('reason_system', 128)->nullable();
            $table->string('reason_display', 255)->nullable();
            $table->foreignId('location_id')->nullable();
            $table->string('location_type', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('language', 35)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('patient_id');
            $table->index('encounter_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
