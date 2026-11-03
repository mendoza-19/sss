<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Oferta;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PedidosOfertasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear clientes de prueba con sus usuarios
        $clientesData = [
            [
                'name'     => 'Carlos Martinez',
                'email'    => 'carlos.martinez@gmail.com',
                'nombre'   => 'Carlos',
                'apellido' => 'Martínez',
                'telefono' => '7845-1234',
                'direccion'=> 'Colonia Escalón, Calle El Mirador #124, San Salvador',
            ],
            [
                'name'     => 'María Gómez',
                'email'    => 'maria.gomez@gmail.com',
                'nombre'   => 'María',
                'apellido' => 'Gómez',
                'telefono' => '7123-4567',
                'direccion'=> 'Boulevard Los Próceres, Condominio Vista Alegre #3B',
            ],
            [
                'name'     => 'Ricardo Mendoza',
                'email'    => 'ricardo.mendoza@gmail.com',
                'nombre'   => 'Ricardo',
                'apellido' => 'Mendoza',
                'telefono' => '7999-8877',
                'direccion'=> 'Residencial Santa Teresa, Senda Los Pinos #45, Santa Tecla',
            ],
            [
                'name'     => 'Ana Sofía Lopez',
                'email'    => 'ana.lopez@gmail.com',
                'nombre'   => 'Ana',
                'apellido' => 'López',
                'telefono' => '7011-2233',
                'direccion'=> 'Avenida Roosevelt Norte #512, San Salvador',
            ],
        ];

        $createdClientes = [];

        foreach ($clientesData as $c) {
            $user = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name'           => $c['name'],
                    'password'       => Hash::make('password123'),
                    'iscustomer'     => 1,
                    'googlepassword' => 0,
                    'email_verified_at' => now(),
                ]
            );

            $cliente = Cliente::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nombre'        => $c['nombre'],
                    'apellido'      => $c['apellido'],
                    'telefono'      => $c['telefono'],
                    'fecharegistro' => Carbon::now()->subMonths(2),
                    'foto'          => null,
                ]
            );

            $createdClientes[] = [
                'cliente'   => $cliente,
                'direccion' => $c['direccion'],
            ];
        }

        // 2. Crear pedidos de prueba
        $estados = [
            ['estado' => 'pendiente', 'dias_atras' => 0],
            ['estado' => 'pendiente', 'dias_atras' => 2], // será atrasado por antigüedad (> 24 horas)
            ['estado' => 'atrasado',  'dias_atras' => 3],
            ['estado' => 'en camino', 'dias_atras' => 0],
            ['estado' => 'entregado', 'dias_atras' => 0, 'actualizado_hoy' => true],
            ['estado' => 'entregado', 'dias_atras' => 1],
        ];

        foreach ($estados as $index => $item) {
            $clienteItem = $createdClientes[$index % count($createdClientes)];
            $createdAt = Carbon::now()->subDays($item['dias_atras'])->subHours(2);
            $updatedAt = !empty($item['actualizado_hoy']) ? Carbon::now() : $createdAt;

            $pedido = Pedido::create([
                'direccion'  => $clienteItem['direccion'],
                'estado'     => $item['estado'],
                'cliente_id' => $clienteItem['cliente']->idcliente,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }

        // 3. Crear ofertas de prueba si hay productos disponibles
        $productos = Producto::all();
        if ($productos->count() > 0 && count($createdClientes) > 0) {
            Oferta::firstOrCreate(
                [
                    'cliente_id' => $createdClientes[0]['cliente']->idcliente,
                    'producto'   => $productos[0]->idproducto,
                ],
                [
                    'descuento'   => 15, // 15%
                    'fechainicio' => Carbon::now()->subDays(2)->format('Y-m-d'),
                    'fechafin'    => Carbon::now()->addDays(10)->format('Y-m-d'),
                ]
            );

            if ($productos->count() > 1 && count($createdClientes) > 1) {
                Oferta::firstOrCreate(
                    [
                        'cliente_id' => $createdClientes[1]['cliente']->idcliente,
                        'producto'   => $productos[1]->idproducto,
                    ],
                    [
                        'descuento'   => 20,
                        'fechainicio' => Carbon::now()->format('Y-m-d'),
                        'fechafin'    => Carbon::now()->addDays(5)->format('Y-m-d'),
                    ]
                );
            }
        }
    }
}
