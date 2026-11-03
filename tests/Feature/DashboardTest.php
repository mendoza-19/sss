<?php

use App\Models\Empleado;
use App\Models\Detalleproducto;
use App\Models\GastoMensual;
use App\Models\Preparacion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Models\Ventaambulante;

it('returns all outstanding sale balances after converting abonos from cents', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de dashboard',
        'estado' => 'Activo',
    ]);

    $sale = Venta::create([
        'emision' => '2026-10-16',
        'fechapago' => null,
        'total' => 10.00,
        'estado' => 'Pendiente',
        'descuento' => 0,
        'empleado_id' => $employee->idempleado,
    ]);
    $sale->abonos()->create([
        'fechaabono' => now()->toDateString(),
        'cantidadabonada' => 550,
        'metodopago' => 'Efectivo',
    ]);

    $sellerRole = Rol::create(['descripcion' => 'Vendedor']);
    $sellerUser = User::factory()->create(['email_verified_at' => now()]);
    $seller = Empleado::create([
        'idusuario' => $sellerUser->id,
        'idrol' => $sellerRole->idrol,
        'nombreCompleto' => 'Vendedor de prueba',
        'estado' => 'Activo',
    ]);
    $assignment = Ventaambulante::create([
        'empleado_id' => $seller->idempleado,
        'unidadesemitidas' => 24,
    ]);
    Venta::create([
        'emision' => now()->toDateString(),
        'fechapago' => null,
        'total' => 2.50,
        'estado' => 'Pendiente',
        'descuento' => 0,
        'empleado_id' => $seller->idempleado,
        'ventaambulante_id' => $assignment->idventaambulante,
    ]);

    $this->actingAs($user)
        ->getJson(route('totalPorCobrar'))
        ->assertOk()
        ->assertJsonPath('response.total_cobrar', 7)
        ->assertJsonPath('response.clientes', 4.5)
        ->assertJsonPath('response.ventas_ambulantes', 2.5);

    $this->actingAs($user)
        ->getJson(route('dashboard.gananciasDelDia'))
        ->assertOk()
        ->assertJsonPath('response.total_ganancias', 5.5);

    $this->actingAs($user)
        ->getJson(route('ventasPorMes'))
        ->assertOk()
        ->assertJsonPath('response.' . (now()->month - 1) . '.cobros', 5.5)
        ->assertJsonPath('response.' . (now()->month - 1) . '.ventas', 2.50);
});

it('returns yearly monthly production and loss counts for the dashboard chart', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de producción',
        'estado' => 'Activo',
    ]);
    $recipe = \App\Models\Receta::create([
        'nombre' => 'Receta de prueba de producción',
        'descripcion' => 'Prueba',
        'instructivo' => 'Prueba',
    ]);

    \App\Models\Preparacion::create([
        'receta_id' => $recipe->idreceta,
        'fecha' => now()->toDateString(),
        'unidades_producidas' => 45,
        'unidades_quemadas' => 5,
        'costo_total' => 12,
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.produccionPorMes'))
        ->assertOk()
        ->assertJsonPath('response.' . (now()->month - 1) . '.buenas', 45)
        ->assertJsonPath('response.' . (now()->month - 1) . '.quemadas', 5);
});

it('returns a monthly sales breakdown including estimated product costs and other expenses', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador financiero',
        'estado' => 'Activo',
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta financiera',
        'descripcion' => 'Prueba',
        'instructivo' => 'Prueba',
    ]);
    $sale = Venta::create([
        'emision' => '2026-11-02',
        'fechapago' => '2026-11-02',
        'total' => 50,
        'estado' => 'Pagada',
        'descuento' => 0,
        'empleado_id' => $employee->idempleado,
    ]);
    Preparacion::create([
        'receta_id' => $recipe->idreceta,
        'fecha' => '2026-11-02',
        'costo_total' => 20,
    ]);
    GastoMensual::create([
        'categoria' => 'agua',
        'mes' => '2026-11-01',
        'monto' => 5,
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.desgloseMensual', ['mes' => '2026-11']))
        ->assertOk()
        ->assertJsonPath('response.ventas', 50)
        ->assertJsonPath('response.costo_productos', 20)
        ->assertJsonPath('response.otros_gastos', 5)
        ->assertJsonPath('response.saldo_estimado', 25)
        ->assertJsonPath('response.deficit_estimado', 0);
});

it('returns matching monthly and daily series for the dashboard filter', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador analítico',
        'estado' => 'Activo',
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta analítica',
        'descripcion' => 'Prueba',
        'instructivo' => 'Prueba',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan analítico',
        'estado' => 'available',
        'visible' => true,
        'stock' => 10,
        'foto' => '',
        'descripcion' => 'Prueba',
        'precio' => 6.25,
        'costo' => 2,
        'receta_id' => $recipe->idreceta,
    ]);
    $sale = Venta::create([
        'emision' => today()->toDateString(),
        'fechapago' => today()->toDateString(),
        'total' => 50,
        'estado' => 'Pagada',
        'descuento' => 0,
        'empleado_id' => $employee->idempleado,
    ]);
    Detalleproducto::create([
        'producto_id' => $product->idproducto,
        'venta_id' => $sale->idventa,
        'cantidad' => 8,
        'subtotal' => 50,
    ]);
    Preparacion::create([
        'receta_id' => $recipe->idreceta,
        'fecha' => today()->toDateString(),
        'unidades_producidas' => 45,
        'unidades_quemadas' => 5,
        'costo_total' => 20,
    ]);
    GastoMensual::create([
        'categoria' => 'agua',
        'mes' => today()->startOfMonth()->toDateString(),
        'fecha' => today()->toDateString(),
        'monto' => 5,
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.analitica', ['modo' => 'mes', 'periodo' => now()->format('Y')]))
        ->assertOk()
        ->assertJsonPath('response.ventas_totales', 50)
        ->assertJsonPath('response.cobros_totales', 50)
        ->assertJsonPath('response.costo_produccion', 20)
        ->assertJsonPath('response.otros_gastos', 5)
        ->assertJsonPath('response.serie.' . (now()->month - 1) . '.ventas', 50)
        ->assertJsonPath('response.serie.' . (now()->month - 1) . '.buenas', 45)
        ->assertJsonPath('response.productos.0.nombre', 'Pan analítico');

    $this->actingAs($user)
        ->getJson(route('dashboard.analitica', ['modo' => 'dia', 'periodo' => now()->format('Y-m')]))
        ->assertOk()
        ->assertJsonPath('response.serie.' . (now()->day - 1) . '.ventas', 50)
        ->assertJsonPath('response.serie.' . (now()->day - 1) . '.cobros', 50)
        ->assertJsonPath('response.serie.' . (now()->day - 1) . '.quemadas', 5);
});