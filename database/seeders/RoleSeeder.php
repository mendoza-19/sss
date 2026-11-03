<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rol')->upsert([
            ['idrol' => 1, 'descripcion' => 'Administrador'],
            ['idrol' => 2, 'descripcion' => 'Vendedor'],
            ['idrol' => 3, 'descripcion' => 'Panadero'],
            ['idrol' => 4, 'descripcion' => 'Repartidor'],
        ], ['idrol'], ['descripcion']);
    }
}