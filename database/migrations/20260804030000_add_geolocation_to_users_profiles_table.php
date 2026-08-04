<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_profiles', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('phone_landline')->constrained('states')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->after('state_id')->constrained('municipalities')->nullOnDelete();
            $table->string('address')->nullable()->after('municipality_id');
        });
    }

    public function down(): void
    {
        Schema::table('users_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropColumn('address');
        });
    }
};
