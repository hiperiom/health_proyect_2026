<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('health_backgrounds')) {
            return;
        }

        Schema::create('health_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('health_backgrounds'); }
};