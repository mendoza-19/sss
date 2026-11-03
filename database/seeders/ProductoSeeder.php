<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Receta;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receta = Receta::firstOrCreate(
            ['nombre' => 'Receta Tradicional'],
            [
                'descripcion' => 'Receta base para panes artesanales de la casa.',
                'instructivo' => 'Mezclar ingredientes, fermentar durante 12 horas y hornear a 200°C.'
            ]
        );

        $productos = [
            [
                'nombre' => 'Pan Artesanal',
                'estado' => 'activo',
                'stock' => 50,
                'foto' => '',
                'descripcion' => 'Pan de trigo grande hecho con masa madre tradicional.',
                'precio' => 1, // $1.00
                'receta_id' => $receta->idreceta,
            ],
            [
                'nombre' => 'Baguette Rústica',
                'estado' => 'activo',
                'visible' => false,
                'stock' => 40,
                'foto' => '',
                'descripcion' => 'Baguette crujiente horneada al fuego de leña.',
                'precio' => 1.25,
                'receta_id' => $receta->idreceta,
            ],
            [
                'nombre' => 'Buñuelo Glaseado',
                'estado' => 'activo',
                'stock' => 60,
                'foto' => '',
                'descripcion' => 'Buñuelo dulce suave y esponjoso con glaseado especial.',
                'precio' => 0.75,
                'receta_id' => $receta->idreceta,
            ],
            [
                'nombre' => 'Croissant de Mantequilla',
                'estado' => 'activo',
                'stock' => 35,
                'foto' => '',
                'descripcion' => 'Hojaldre mantequilloso dorado de sabor exquisito.',
                'precio' => 1.50,
                'receta_id' => $receta->idreceta,
            ],
            [
                'nombre' => 'Concha de Chocolate',
                'estado' => 'activo',
                'stock' => 45,
                'foto' => '',
                'descripcion' => 'Pan dulce tradicional con cobertura crujiente de chocolate.',
                'precio' => 0.90,
                'receta_id' => $receta->idreceta,
            ],
            [
                'nombre' => 'Pastel de Tres Leches',
                'estado' => 'activo',
                'stock' => 15,
                'foto' => '',
                'descripcion' => 'Porción de suave bizcocho bañado en mezcla de tres leches.',
                'precio' => 3.50,
                'receta_id' => $receta->idreceta,
            ],
        ];

        foreach ($productos as $prod) {
            $producto = Producto::firstOrCreate(
                ['nombre' => $prod['nombre']],
                $prod
            );

            if (array_key_exists('visible', $prod) && $producto->visible !== $prod['visible']) {
                $producto->visible = $prod['visible'];
                $producto->save();
            }
        }
    }
}
