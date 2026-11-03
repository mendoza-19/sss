<?php

use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Receta;
use App\Models\Rol;
use App\Models\Venta;
use App\Models\User;

it('registra encargos futuros de productos activos e inactivos sin requerir stock', function () {
    $receta = Receta::create([
        'nombre' => 'Receta de prueba para encargo',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);

    $producto = Producto::create([
        'nombre' => 'Pan grande de prueba',
        'estado' => 'available',
        'visible' => true,
        'stock' => 0,
        'foto' => '',
        'descripcion' => 'Producto para encargo.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $receta->idreceta,
    ]);
    $productoInactivo = Producto::create([
        'nombre' => 'Producto inactivo para encargo',
        'estado' => 'inactivo',
        'visible' => true,
        'stock' => 0,
        'foto' => '',
        'descripcion' => 'Producto inactivo para encargo.',
        'precio' => 2.00,
        'costo' => 1.00,
        'receta_id' => $receta->idreceta,
    ]);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('menu'))
        ->assertOk()
        ->assertSee('Pan grande de prueba')
        ->assertSee('Producto inactivo para encargo');

    $fechaEntrega = today()->addDay()->toDateString();
    $response = $this->actingAs($user)
        ->postJson(route('api.pedidos.cliente.encargo'), [
            'direccion' => 'Avenida Central 123',
            'fecha_entrega' => $fechaEntrega,
            'items' => [
                ['id' => $producto->idproducto, 'quantity' => 100],
                ['id' => $productoInactivo->idproducto, 'quantity' => 3],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $pedidoId = $response->json('pedido_id');
    $this->assertDatabaseHas('pedidos', [
        'idpedido' => $pedidoId,
        'tipo' => 'encargo',
    ]);
    expect(Pedido::findOrFail($pedidoId)->fecha_entrega->toDateString())->toBe($fechaEntrega);
    $this->assertDatabaseHas('detalleproductos', [
        'pedido_id' => $pedidoId,
        'producto_id' => $producto->idproducto,
        'cantidad' => 100,
    ]);
    $this->assertDatabaseHas('detalleproductos', [
        'pedido_id' => $pedidoId,
        'producto_id' => $productoInactivo->idproducto,
        'cantidad' => 3,
    ]);
    expect($producto->fresh()->stock)->toBe(0);
    expect($productoInactivo->fresh()->stock)->toBe(0);

    $user = User::findOrFail($user->id);
    $this->actingAs($user)
        ->get(route('perfil'))
        ->assertOk()
        ->assertSee('Encargo #' . $pedidoId)
        ->assertSee('Pan grande de prueba')
        ->assertSee('Producto inactivo para encargo')
        ->assertSee('Monto estimado: $131.00')
        ->assertSee('Aún no tienes compras registradas.')
        ->assertDontSee('Pedido #1042');

    $employeeUser = User::factory()->create(['email' => 'empleado-encargo@test.com']);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $employeeUser->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Empleado de prueba',
        'estado' => 'Activo',
    ]);
    $sale = Venta::create([
        'emision' => today()->toDateString(),
        'fechapago' => today()->toDateString(),
        'total' => 131.00,
        'estado' => 'Pagada',
        'descuento' => 0,
        'empleado_id' => $employee->idempleado,
    ]);

    $pedido = Pedido::with('detalles')->findOrFail($pedidoId);
    foreach ($pedido->detalles as $detalle) {
        $detalle->venta_id = $sale->idventa;
        $detalle->save();
    }
    $pedido->estado = 'procesado';
    $pedido->save();

    $this->actingAs($user)
        ->get(route('perfil'))
        ->assertOk()
        ->assertSee('No tienes pagos pendientes.')
        ->assertSee('Compra #' . $sale->idventa)
        ->assertSee('Pedido #' . $pedidoId)
        ->assertSee('Procesado')
        ->assertSee('Pan grande de prueba')
        ->assertDontSee('Aún no tienes compras registradas.');
});

it('shows menu orders as debt until the assigned delivery worker marks them paid', function () {
    $customer = User::factory()->create([
        'name' => 'Cliente con deuda de menú',
        'email' => 'cliente-deuda-menu@test.com',
        'email_verified_at' => now(),
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta deuda menú',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan con pago pendiente',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Producto para verificar deuda.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);

    $orderResponse = $this->actingAs($customer)->postJson(route('api.pedidos.cliente.crear'), [
        'direccion' => 'Avenida Central 123',
        'items' => [['id' => $product->idproducto, 'quantity' => 2]],
    ]);
    $orderResponse->assertOk();
    $pedidoId = $orderResponse->json('pedido_id');

    expect(Pedido::findOrFail($pedidoId)->estado_pago)->toBe('pendiente');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $adminRole = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $adminRole->idrol,
        'nombreCompleto' => 'Administrador deuda',
        'estado' => 'Activo',
    ]);

    $ordersToAssign = $this->actingAs($admin)->postJson(route('api.pedidos.getPedidosPagination'), [
        'estado' => 'por-asignar',
        'limit' => 50,
        'skip' => 0,
    ]);
    $ordersToAssign->assertOk()
        ->assertJsonCount(1, 'pedidos')
        ->assertJsonPath('pedidos.0.idpedido', $pedidoId);

    $debtResponse = $this->actingAs($admin)->postJson(route('clientes-por-deuda'), ['limit' => 6, 'skip' => 0]);
    $debtResponse->assertOk();
    expect((float) $debtResponse->json('response.0.deuda'))->toBe(2.5);

    $deliveryUser = User::factory()->create([
        'email' => 'repartidor-deuda-menu@test.com',
        'email_verified_at' => now(),
    ]);
    $deliveryRole = Rol::create(['descripcion' => 'Repartidor']);
    $deliveryEmployee = Empleado::create([
        'idusuario' => $deliveryUser->id,
        'idrol' => $deliveryRole->idrol,
        'nombreCompleto' => 'Repartidor deuda menú',
        'estado' => 'Activo',
    ]);
    Pedido::whereKey($pedidoId)->update(['repartidor_id' => $deliveryEmployee->idempleado]);

    $this->actingAs($deliveryUser)
        ->postJson(route('api.pedidos.cambiarEstadoPago'), [
            'idpedido' => $pedidoId,
            'estado_pago' => 'pagada',
        ])
        ->assertOk()
        ->assertJsonPath('estado_pago', 'pagada');

    expect(Pedido::findOrFail($pedidoId)->fechapago->toDateString())->toBe(today()->toDateString());

    $sale = Venta::whereHas('detalleproductos', fn ($query) => $query->where('pedido_id', $pedidoId))->firstOrFail();
    $this->assertDatabaseHas('abonos', [
        'venta_id' => $sale->idventa,
        'fechaabono' => today()->toDateString(),
        'cantidadabonada' => 250,
        'metodopago' => 'Tarjeta simulada',
    ]);

    $this->actingAs($deliveryUser)
        ->postJson(route('api.pedidos.cambiarEstadoPago'), [
            'idpedido' => $pedidoId,
            'estado_pago' => 'pagada',
        ])
        ->assertOk();
    expect((int) $sale->abonos()->sum('cantidadabonada'))->toBe(250);

    $debtResponse = $this->actingAs($admin)->postJson(route('clientes-por-deuda'), ['limit' => 6, 'skip' => 0]);
    $debtResponse->assertOk()->assertJsonPath('response', []);
});

it('advances order production one stage at a time for administrators', function () {
    $customer = User::factory()->create();
    $recipe = Receta::create([
        'nombre' => 'Receta para flujo de producción',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan para flujo de producción',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Producto de prueba.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);
    $orderResponse = $this->actingAs($customer)->postJson(route('api.pedidos.cliente.crear'), [
        'direccion' => 'Avenida Central 123',
        'items' => [['id' => $product->idproducto, 'quantity' => 1]],
    ]);
    $orderResponse->assertOk();
    $pedidoId = $orderResponse->json('pedido_id');

    $admin = User::factory()->create();
    $adminRole = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $adminRole->idrol,
        'nombreCompleto' => 'Administrador producción',
        'estado' => 'Activo',
    ]);

    $this->actingAs($admin)->postJson(route('api.pedidos.cambiarEstadoProduccion'), [
        'idpedido' => $pedidoId,
        'estado_produccion' => 'horneado',
    ])->assertUnprocessable();

    foreach (['en_preparacion', 'horneado', 'terminado'] as $etapa) {
        $this->actingAs($admin)->postJson(route('api.pedidos.cambiarEstadoProduccion'), [
            'idpedido' => $pedidoId,
            'estado_produccion' => $etapa,
        ])->assertOk()->assertJsonPath('estado_produccion', $etapa);
        $this->assertDatabaseHas('pedidos', [
            'idpedido' => $pedidoId,
            'estado_produccion' => $etapa,
        ]);
    }

    $this->actingAs($admin)->postJson(route('api.pedidos.cambiarEstadoProduccion'), [
        'idpedido' => $pedidoId,
        'estado_produccion' => 'pendiente',
    ])->assertOk()->assertJsonPath('estado_produccion', 'pendiente');
    $this->assertDatabaseHas('pedidos', [
        'idpedido' => $pedidoId,
        'estado_produccion' => 'pendiente',
    ]);

    expect(Pedido::findOrFail($pedidoId)->estado)->toBe('pendiente');
});

it('shows menu-cart orders and only production-finished encargos in delivery orders', function () {
    $customer = User::factory()->create();
    $recipe = Receta::create([
        'nombre' => 'Receta para filtrar pedidos',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan para filtrar pedidos',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Producto de prueba.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);

    $menuOrderResponse = $this->actingAs($customer)->postJson(route('api.pedidos.cliente.crear'), [
        'direccion' => 'Avenida Central 123',
        'items' => [['id' => $product->idproducto, 'quantity' => 1]],
    ]);
    $menuOrderResponse->assertOk();
    $menuOrderId = $menuOrderResponse->json('pedido_id');

    $encargoResponse = $this->actingAs($customer)->postJson(route('api.pedidos.cliente.encargo'), [
        'direccion' => 'Avenida Central 123',
        'fecha_entrega' => today()->addDay()->toDateString(),
        'items' => [['id' => $product->idproducto, 'quantity' => 1]],
    ]);
    $encargoResponse->assertCreated();
    $encargoId = $encargoResponse->json('pedido_id');

    $admin = User::factory()->create();
    $adminRole = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $adminRole->idrol,
        'nombreCompleto' => 'Administrador de pedidos listos',
        'estado' => 'Activo',
    ]);

    $listResponse = $this->actingAs($admin)->postJson(route('api.pedidos.getPedidosPagination'), [
        'estado' => 'todos',
        'limit' => 50,
        'skip' => 0,
    ]);
    $listResponse->assertOk()
        ->assertJsonCount(1, 'pedidos')
        ->assertJsonPath('pedidos.0.idpedido', $menuOrderId);

    Pedido::whereKey($encargoId)->update(['estado_produccion' => 'terminado']);

    $listResponse = $this->actingAs($admin)->postJson(route('api.pedidos.getPedidosPagination'), [
        'estado' => 'todos',
        'limit' => 50,
        'skip' => 0,
    ]);
    $listResponse->assertOk()->assertJsonCount(2, 'pedidos');
    expect(collect($listResponse->json('pedidos'))->pluck('idpedido')->map(fn ($id) => (int) $id)->all())
        ->toContain((int) $menuOrderId, (int) $encargoId);

    $statsResponse = $this->actingAs($admin)->getJson(route('api.pedidos.estadisticas'));
    $statsResponse->assertOk()->assertJsonPath('pendientes', 2);
});

