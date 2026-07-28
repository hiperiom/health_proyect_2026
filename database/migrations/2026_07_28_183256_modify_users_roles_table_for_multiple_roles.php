<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_roles', function (Blueprint $table) {
            $table->dropUnique('users_roles_user_id_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users_roles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->unique('user_id');
        });
    }
};
