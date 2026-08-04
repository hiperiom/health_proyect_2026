<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained('encounters');
            $table->foreignId('requester_id')->nullable()->constrained('users');
            $table->string('code', 64);
            $table->string('code_system', 16);
            $table->string('code_display', 255)->nullable();
            $table->string('status', 32);
            $table->string('priority', 32)->nullable();
            $table->timestampTz('ordered_at');
            $table->timestampTz('scheduled_for')->nullable();
            $table->string('body_site', 128)->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('encounter_id');
            $table->index(['code_system', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
