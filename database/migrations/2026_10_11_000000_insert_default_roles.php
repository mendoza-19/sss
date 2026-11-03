<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Administrador', 'Vendedor', 'Panadero', 'Repartidor'] as $descripcion) {
            if (! DB::table('rol')->where('descripcion', $descripcion)->exists()) {
                DB::table('rol')->insert(['descripcion' => $descripcion]);
            }
        }
    }

    public function down(): void
    {
    }
};