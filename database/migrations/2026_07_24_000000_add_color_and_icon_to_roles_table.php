<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('color_class')->nullable()->after('slug');
            $table->string('text_class')->nullable()->after('color_class');
            $table->text('icon_svg')->nullable()->after('text_class');
        });

        // Update existing roles with color classes and inline SVG icons
        $roles = [
            'superusuario' => [
                'color_class' => 'bg-rose-50',
                'text_class' => 'text-rose-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a7 7 0 0 0 0-6"/></svg>',
            ],
            'administrador' => [
                'color_class' => 'bg-indigo-50',
                'text_class' => 'text-indigo-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 3v4M8 3v4"/></svg>',
            ],
            'doctor' => [
                'color_class' => 'bg-emerald-50',
                'text_class' => 'text-emerald-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M5 12h14"/></svg>',
            ],
            'enfermeria' => [
                'color_class' => 'bg-amber-50',
                'text_class' => 'text-amber-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v10"/><path d="M9 10h6"/></svg>',
            ],
            'asistencial' => [
                'color_class' => 'bg-sky-50',
                'text_class' => 'text-sky-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M12 3v18"/></svg>',
            ],
            'paciente' => [
                'color_class' => 'bg-gray-100',
                'text_class' => 'text-gray-800',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="3"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>',
            ],
        ];

        foreach ($roles as $slug => $data) {
            DB::table('roles')->where('slug', $slug)->update([
                'color_class' => $data['color_class'],
                'text_class' => $data['text_class'],
                'icon_svg' => $data['icon_svg'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['color_class', 'text_class', 'icon_svg']);
        });
    }
};
