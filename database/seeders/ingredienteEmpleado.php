<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ingredienteEmpleado extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedorTable = 'proveedors';

        if(app()->isProduction()){
            throw new RuntimeException('ingredienteEmpleado solo puede ejecutarse fuera de produccion');
        }

        $proveedores = [
            ['nombre' => 'Suministros La Espiga', 'telefono' => '52364528', 'correo' => 'espiga@gamail.com'],
            ['nombre' => 'Distribuidora El Trigal', 'telefono' => '93652417', 'correo' => 'trigal@gmail.com'],
            ['nombre' => 'Insumos San Miguel', 'telefono' => '93642578', 'correo' => 'sanmiguel@gmail.com'],
            ['nombre' => 'Materias Primas Central', 'telefono' => '11243697', 'correo' => 'central@gmail.com'],
            ['nombre' => 'Comercial La Harinera', 'telefono' => '98231648', 'correo' => 'harinera@gmail.com'],
            ['nombre' => 'Comercial La Holeada', 'telefono' => '46397852', 'correo' => 'holeada@gmail.com'],
            ['nombre' => 'Comercial La Nueva Esperanza ', 'telefono' => '71364528', 'correo' => 'esperanza@gmail.com'],
            ['nombre' => 'Comercial La San Antonio', 'telefono' => '36452879', 'correo' => 'sanantonio@example.test']
        ];

        /*shuffle($proveedores);*/
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
                'puntuacion' => random_int(0, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
        }

        $ingredientes = [
            ['nombre' => 'Harina', 'descripcion' => 'Harina de trigo para panadería.'],
            ['nombre' => 'Manteca', 'descripcion' => 'Manteca para preparación de masas.'],
            ['nombre' => 'Sal', 'descripcion' => 'Sal para elaboración de productos de panadería.'],
            ['nombre' => 'Levadura', 'descripcion' => 'Levadura para fermentación de masas.'],
            ['nombre' => 'Azucar', 'descripcion' => 'Azucar para boyos azucarados.'],
            ['nombre' => 'Ajonjoli', 'descripcion' => 'Semillas granuladas para panes artesanales.'],
            ['nombre' => 'Pasas', 'descripcion' => 'Pazas para panes de fruta.'],
            ['nombre' => 'Leche', 'descripcion' => 'Leche para pan de queso.'],
            ['nombre' => 'Queso Chedar', 'descripcion' => 'Queso chedar para el pan de queso.']
        ];

        foreach ($ingredientes as $ingrediente) {
            $existe = DB::table('ingredientes')
                ->whereRaw('LOWER(nombre) = ?', [strtolower($ingrediente['nombre'])])
                ->exists();

            if (!$existe) {
                DB::table('ingredientes')->insert([
                    ...$ingrediente,
                    'stock' => random_int(10, 500),
                    'medida' => 'lb',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
