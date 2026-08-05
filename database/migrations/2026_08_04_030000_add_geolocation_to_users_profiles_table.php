<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_profiles')) {
            return;
        }

        Schema::table('users_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('users_profiles', 'state_id')) {
                $table->foreignId('state_id')->nullable()->after('phone_landline')->constrained('states')->nullOnDelete();
            }

            if (! Schema::hasColumn('users_profiles', 'municipality_id')) {
                $table->foreignId('municipality_id')->nullable()->after('state_id')->constrained('municipalities')->nullOnDelete();
            }

            if (! Schema::hasColumn('users_profiles', 'address')) {
                $table->string('address')->nullable()->after('municipality_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('users_profiles', 'state_id')) {
                $table->dropConstrainedForeignId('state_id');
            }

            if (Schema::hasColumn('users_profiles', 'municipality_id')) {
                $table->dropConstrainedForeignId('municipality_id');
            }

            if (Schema::hasColumn('users_profiles', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
