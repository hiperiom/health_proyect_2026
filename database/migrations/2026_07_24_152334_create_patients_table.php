<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('photo_path')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('nacionality', 20)->default('V');
            $table->string('dni', 50)->unique();
            $table->date('birth_date');
            $table->string('gender', 20);
            $table->string('phone_mobile', 30);
            $table->string('phone_landline', 30)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
