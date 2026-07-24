<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
        });

        // Populate display_name for existing rows with a human readable form
        // derived from the technical name (User -> User, medical_especiality
        // -> Medical Especiality).
        foreach (DB::table('modules')->get() as $row) {
            $display = Str::headline($row->name);

            DB::table('modules')
                ->where('id', $row->id)
                ->update(['display_name' => $display]);
        }
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
