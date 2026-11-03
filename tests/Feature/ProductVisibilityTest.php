<?php

use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Rol;
use App\Models\User;

it('hides invisible products from the public menu, inventory, API, and sales selector', function () {
    $receta = Receta::create([
        'nombre' => 'Receta para prueba de visibilidad',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);

    Producto::create([
        'nombre' => 'Producto visible de prueba',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Debe aparecer en los catálogos.',
        'precio' => 1.00,
        'costo' => 0.50,
        'receta_id' => $receta->idreceta,
    ]);

    Producto::create([
        'nombre' => 'Baguette Rústica',
        'estado' => 'activo',
        'visible' => false,
        'stock' => 40,
        'foto' => '',
        'descripcion' => 'Debe permanecer oculta.',
        'precio' => 1.25,
        'costo' => 0.00,
        'receta_id' => $receta->idreceta,
    ]);

    $this->get(route('menu'))
        ->assertOk()
        ->assertSee('Producto visible de prueba')
        ->assertDontSee('Baguette');

    $this->get(route('api.productos'))
        ->assertOk()
        ->assertJsonFragment(['nombre' => 'Producto visible de prueba'])
        ->assertJsonMissing(['nombre' => 'Baguette Rústica']);

    $administrator = User::factory()->create([
        'email_verified_at' => now(),
        'iscustomer' => false,
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $administrator->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => $administrator->name,
        'estado' => 'Activo',
    ]);

    $this->actingAs($administrator)
        ->get(route('productos'))
        ->assertOk()
        ->assertSee('Producto visible de prueba')
        ->assertDontSee('Baguette Rústica');

    $this->actingAs($administrator)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Producto visible de prueba')
        ->assertDontSee('Baguette Rústica');
});