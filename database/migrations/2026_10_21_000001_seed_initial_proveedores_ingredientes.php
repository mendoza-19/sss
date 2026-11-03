<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $proveedorTable = Schema::hasTable('proveedors')
            ? 'proveedors'
            : (Schema::hasTable('proveedores') ? 'proveedores' : null);

        if (!$proveedorTable || !Schema::hasTable('ingredientes')) {
            throw new RuntimeException('Las tablas de proveedores e ingredientes deben existir antes de cargar sus datos iniciales.');
        }

        $proveedores = [
            ['nombre' => 'Suministros La Espiga', 'telefono' => '7012-1001', 'correo' => 'espiga@example.test'],
            ['nombre' => 'Distribuidora El Trigal', 'telefono' => '7012-1002', 'correo' => 'trigal@example.test'],
            ['nombre' => 'Insumos San Miguel', 'telefono' => '7012-1003', 'correo' => 'sanmiguel@example.test'],
            ['nombre' => 'Materias Primas Central', 'telefono' => '7012-1004', 'correo' => 'central@example.test'],
            ['nombre' => 'Comercial La Harinera', 'telefono' => '7012-1005', 'correo' => 'harinera@example.test'],
        ];

        shuffle($proveedores);
        foreach ($proveedores as $proveedor) {
            if (DB::table($proveedorTable)->count() >= 5) {
                break;
            }

            if (DB::table($proveedorTable)->where('nombre', $proveedor['nombre'])->exists()) {
                continue;
            }

            DB::table($proveedorTable)->insert([
                ...$proveedor,
                'descripcion' => 'Proveedor de materias primas para panadería.',
                'puntuacion' => random_int(1, 5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ingredientes = [
            ['nombre' => 'Harina', 'descripcion' => 'Harina de trigo para panadería.'],
            ['nombre' => 'Manteca', 'descripcion' => 'Manteca para preparación de masas.'],
            ['nombre' => 'Sal', 'descripcion' => 'Sal para elaboración de productos de panadería.'],
            ['nombre' => 'Levadura', 'descripcion' => 'Levadura para fermentación de masas.'],
        ];

        foreach ($ingredientes as $ingrediente) {
            $existe = DB::table('ingredientes')
                ->whereRaw('LOWER(nombre) = ?', [strtolower($ingrediente['nombre'])])
                ->exists();

            if (!$existe) {
                DB::table('ingredientes')->insert([
                    ...$ingrediente,
                    'stock' => 0,
                    'medida' => 'lb',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
