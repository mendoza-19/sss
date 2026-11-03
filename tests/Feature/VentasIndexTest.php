<?php

use App\Models\Empleado;
use App\Models\Detalleproducto;
use App\Models\Cliente;
use App\Models\Oferta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Models\Ventaambulante;

it('includes customer order products in the sales processing selector', function () {
    $user = User::factory()->create([
        'name' => 'Administrador de ventas',
        'email' => 'admin-pedidos@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de ventas',
        'estado' => 'Activo',
    ]);

    $recipe = Receta::create([
        'nombre' => 'Receta para el pedido',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan del encargo de prueba',
        'estado' => 'available',
        'visible' => true,
        'stock' => 0,
        'foto' => '',
        'descripcion' => 'Producto para encargo.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);

    $encargoResponse = $this->actingAs($user)->postJson(route('api.pedidos.cliente.encargo'), [
        'direccion' => 'Avenida Central 123',
        'fecha_entrega' => today()->addDay()->toDateString(),
        'items' => [
            ['id' => $product->idproducto, 'quantity' => 4],
        ],
    ]);
    $encargoResponse->assertCreated();
    $pedidoId = $encargoResponse->json('pedido_id');

    $salesResponse = $this->actingAs($user)->get(route('ventas.index'))->assertOk();
    preg_match(
        '/<option value="' . preg_quote((string) $pedidoId, '/') . '"[^>]*data-items="([^"]*)"/',
        $salesResponse->getContent(),
        $matches
    );
    $itemsJson = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5);
    $items = json_decode($itemsJson, true, 512, JSON_THROW_ON_ERROR);

    expect($items)->toHaveCount(1)
        ->and($items[0]['nombre'])->toBe('Pan del encargo de prueba')
        ->and($items[0]['cantidad'])->toBe(4)
        ->and((float) $items[0]['subtotal'])->toBe(5.0);
});

it('keeps processed customer orders out of local sales', function () {
    $customer = User::factory()->create([
        'name' => 'Cliente con historial',
        'email' => 'cliente-historial@test.com',
        'email_verified_at' => now(),
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta para historial',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan del historial',
        'estado' => 'available',
        'visible' => true,
        'stock' => 10,
        'foto' => '',
        'descripcion' => 'Producto para historial.',
        'precio' => 1.50,
        'costo' => 0.75,
        'receta_id' => $recipe->idreceta,
    ]);

    $pedidoIds = [];
    for ($index = 1; $index <= 2; $index++) {
        $response = $this->actingAs(User::findOrFail($customer->id))->postJson(route('api.pedidos.cliente.encargo'), [
            'direccion' => 'Avenida Central 123',
            'fecha_entrega' => today()->addDay()->toDateString(),
            'items' => [
                ['id' => $product->idproducto, 'quantity' => $index],
            ],
        ]);
        $response->assertCreated();
        $pedidoIds[] = $response->json('pedido_id');
    }

    $admin = User::factory()->create([
        'name' => 'Administrador de ventas',
        'email' => 'admin-historial@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de ventas',
        'estado' => 'Activo',
    ]);

    foreach ($pedidoIds as $index => $pedidoId) {
        $quantity = $index + 1;
        $sale = Venta::create([
            'emision' => today()->toDateString(),
            'fechapago' => today()->toDateString(),
            'total' => 1.50 * $quantity,
            'estado' => 'Pagada',
            'descuento' => 0,
            'empleado_id' => $employee->idempleado,
        ]);
        Detalleproducto::where('pedido_id', $pedidoId)->update(['venta_id' => $sale->idventa]);
        Pedido::whereKey($pedidoId)->update(['estado' => 'procesado']);
    }

    Venta::create([
        'emision' => today()->toDateString(),
        'fechapago' => today()->toDateString(),
        'total' => 4.25,
        'estado' => 'Pagada',
        'descuento' => 0,
        'empleado_id' => $employee->idempleado,
    ]);

    $response = $this->actingAs($admin)->get(route('ventas.index'))->assertOk();
    $response->assertSee('Venta local (1)')
        ->assertSee('Procesar pedidos')
        ->assertSee('Pedidos (1)')
        ->assertSee('Ver historial (2)')
        ->assertSee('data-view-client-history');
    expect(substr_count($response->getContent(), 'data-order-history-row'))->toBe(1);
});

it('processes a customer order using the payment date field', function () {
    $customer = User::factory()->create([
        'name' => 'Cliente para cobrar',
        'email' => 'cliente-cobro@test.com',
        'email_verified_at' => now(),
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta para cobrar pedido',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan para cobrar',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Producto para cobrar.',
        'precio' => 1.50,
        'costo' => 0.75,
        'receta_id' => $recipe->idreceta,
    ]);
    $orderResponse = $this->actingAs(User::findOrFail($customer->id))->postJson(route('api.pedidos.cliente.encargo'), [
        'direccion' => 'Avenida Central 123',
        'fecha_entrega' => today()->addDay()->toDateString(),
        'items' => [
            ['id' => $product->idproducto, 'quantity' => 2],
        ],
    ]);
    $orderResponse->assertCreated();
    $pedidoId = $orderResponse->json('pedido_id');
    $cliente = Cliente::where('user_id', $customer->id)->firstOrFail();
    Oferta::create([
        'cliente_id' => $cliente->idcliente,
        'producto' => $product->idproducto,
        'descuento' => 50,
        'fechainicio' => today()->subDay()->toDateString(),
        'fechafin' => today()->addDay()->toDateString(),
    ]);

    $admin = User::factory()->create([
        'name' => 'Administrador de cobros',
        'email' => 'admin-cobro@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    $employee = Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de cobros',
        'estado' => 'Activo',
    ]);

    $salesPage = $this->actingAs($admin)->get(route('ventas.index'))->assertOk();
    $salesPage->assertSee('descuento_oferta&quot;:50', false)
        ->assertSee('Pendiente de pago')
        ->assertSee('data-production-status', false)
        ->assertSee('En preparación')
        ->assertSee('value="en_preparacion"', false)
        ->assertDontSee('value="horneado"', false)
        ->assertDontSee('value="terminado"', false)
        ->assertSee('data-production-ready="false"', false)
        ->assertDontSee('id="process-order-payment-status"', false);

    $this->actingAs($admin)
        ->from(route('ventas.index'))
        ->post(route('ventas.procesar-pedido'), [
            'pedido_id' => $pedidoId,
            'fechapago' => today()->toDateString(),
            'descuento' => 101,
        ])
        ->assertSessionHasErrorsIn('orderSale', 'descuento');

    $this->actingAs($admin)
        ->post(route('ventas.procesar-pedido'), [
            'pedido_id' => $pedidoId,
            'fechapago' => today()->toDateString(),
            'descuento' => 10,
            'estado_pedido' => 'procesado',
        ])
        ->assertRedirect(route('ventas.index'));

    $sale = Venta::query()->latest('idventa')->firstOrFail();
    expect($sale->estado)->toBe('Pagada')
        ->and((float) $sale->total)->toBe(1.35)
        ->and((int) $sale->descuento)->toBe(55)
        ->and(Pedido::findOrFail($pedidoId)->estado)->toBe('procesado')
        ->and(Pedido::findOrFail($pedidoId)->estado_pago)->toBe('pagada')
        ->and($product->fresh()->stock)->toBe(3);
    $this->assertDatabaseHas('abonos', [
        'venta_id' => $sale->idventa,
        'fechaabono' => today()->toDateString(),
        'cantidadabonada' => 135,
        'metodopago' => 'Sin especificar',
    ]);
    $this->assertDatabaseHas('detalleproductos', [
        'pedido_id' => $pedidoId,
        'venta_id' => $sale->idventa,
    ]);
});

it('marks a customer order as paid when its sale is registered', function () {
    $customer = User::factory()->create([
        'name' => 'Cliente sin pago',
        'email' => 'cliente-pendiente@test.com',
        'email_verified_at' => now(),
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta para pedido pendiente',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan pendiente de pago',
        'estado' => 'available',
        'visible' => true,
        'stock' => 5,
        'foto' => '',
        'descripcion' => 'Producto para pedido pendiente.',
        'precio' => 1.50,
        'costo' => 0.75,
        'receta_id' => $recipe->idreceta,
    ]);
    $orderResponse = $this->actingAs($customer)->postJson(route('api.pedidos.cliente.encargo'), [
        'direccion' => 'Avenida Central 123',
        'fecha_entrega' => today()->addDay()->toDateString(),
        'items' => [
            ['id' => $product->idproducto, 'quantity' => 2],
        ],
    ]);
    $orderResponse->assertCreated();
    $pedidoId = $orderResponse->json('pedido_id');

    $admin = User::factory()->create([
        'name' => 'Administrador de cobros pendientes',
        'email' => 'admin-pendiente@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $admin->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de cobros pendientes',
        'estado' => 'Activo',
    ]);

    $this->actingAs($admin)
        ->post(route('ventas.procesar-pedido'), [
            'pedido_id' => $pedidoId,
            'fechapago' => today()->toDateString(),
            'descuento' => 0,
        ])
        ->assertRedirect(route('ventas.index'));

    $sale = Venta::query()->latest('idventa')->firstOrFail();
    expect($sale->estado)->toBe('Pagada')
        ->and($sale->fechapago)->toBe(today()->toDateString())
        ->and(Pedido::findOrFail($pedidoId)->estado)->toBe('procesado')
        ->and(Pedido::findOrFail($pedidoId)->estado_pago)->toBe('pagada')
        ->and($product->fresh()->stock)->toBe(3);
});

it('registers a local encargo for a walk-in customer without a user account', function () {
    $user = User::factory()->create([
        'name' => 'Administrador de encargos',
        'email' => 'admin-encargo-local@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de encargos',
        'estado' => 'Activo',
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta para encargo local',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan para encargo local',
        'estado' => 'unavailable',
        'visible' => true,
        'stock' => 0,
        'foto' => '',
        'descripcion' => 'Producto para encargo local.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);

    $customerResponse = $this->actingAs($user)->postJson(route('api.ventas.clientes.store'), [
        'nombre' => 'María',
        'apellido' => 'López',
        'telefono' => '7000-0000',
    ]);
    $customerResponse->assertCreated();
    $customerId = $customerResponse->json('cliente.id');

    $this->actingAs(User::findOrFail($user->id))
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('id="local-order-client-select"', false)
        ->assertSee('value="' . $customerId . '"', false)
        ->assertSee('María López')
        ->assertSee('id="local-order-product-search"', false)
        ->assertSee('id="local-order-money"', false)
        ->assertSee('data-local-order-product-option', false);

    $this->actingAs(User::findOrFail($user->id))
        ->post(route('ventas.encargos.local.store'), [
            'cliente_id' => $customerId,
            'fecha_entrega' => today()->toDateString(),
            'items' => [
                ['id' => $product->idproducto, 'quantity' => 3],
            ],
        ])
        ->assertRedirect(route('ventas.index'));

    $cliente = Cliente::findOrFail($customerId);
    expect($cliente->user_id)->toBeNull();
    $pedido = $cliente->pedidos()->with('detalles')->firstOrFail();
    expect($pedido->estado)->toBe('pendiente')
        ->and($pedido->tipo)->toBe('encargo_local')
        ->and($pedido->direccion)->toBe('Recogida en local')
        ->and($pedido->detalles->first()->cantidad)->toBe(3);
    expect($product->fresh()->stock)->toBe(0);
});

it('records a local encargo advance and collects only the remaining balance when processed', function () {
    $user = User::factory()->create([
        'name' => 'Administrador de adelantos',
        'email' => 'admin-adelanto@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de adelantos',
        'estado' => 'Activo',
    ]);
    $customerResponse = $this->actingAs($user)->postJson(route('api.ventas.clientes.store'), [
        'nombre' => 'Cliente con adelanto',
        'apellido' => 'Local',
    ]);
    $customerResponse->assertCreated();
    $customerId = $customerResponse->json('cliente.id');

    $recipe = Receta::create([
        'nombre' => 'Receta para encargo con adelanto',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $product = Producto::create([
        'nombre' => 'Pan con adelanto',
        'estado' => 'available',
        'visible' => true,
        'stock' => 10,
        'foto' => '',
        'descripcion' => 'Producto para encargo con adelanto.',
        'precio' => 1.25,
        'costo' => 0.50,
        'receta_id' => $recipe->idreceta,
    ]);

    $this->actingAs($user)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Registrar adelanto');

    $this->actingAs($user)
        ->post(route('ventas.encargos.local.store'), [
            'cliente_id' => $customerId,
            'fecha_entrega' => today()->toDateString(),
            'abono_inicial' => 1.50,
            'items' => [
                ['id' => $product->idproducto, 'quantity' => 3],
            ],
        ])
        ->assertRedirect(route('ventas.index'));

    $pedido = Pedido::with('detalles.venta.abonos')->latest('idpedido')->firstOrFail();
    $venta = $pedido->detalles->first()->venta;
    expect($venta)->not->toBeNull()
        ->and((float) $venta->total)->toBe(3.75)
        ->and($venta->estado)->toBe('Pendiente')
        ->and((int) $venta->abonos->sum('cantidadabonada'))->toBe(150);

    $this->actingAs($user)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('value="' . $pedido->idpedido . '"', false)
        ->assertSee('data-abono="1.50"', false);

    $this->actingAs($user)
        ->post(route('ventas.procesar-pedido'), [
            'pedido_id' => $pedido->idpedido,
            'fechapago' => today()->toDateString(),
            'descuento' => 0,
        ])
        ->assertRedirect(route('ventas.index'));

    $venta->refresh();
    expect($venta->estado)->toBe('Pagada')
        ->and((float) $venta->total)->toBe(3.75)
        ->and((int) $venta->abonos()->sum('cantidadabonada'))->toBe(375)
        ->and($venta->abonos()->count())->toBe(2)
        ->and(Pedido::findOrFail($pedido->idpedido)->estado_pago)->toBe('pagada')
        ->and((int) $product->fresh()->stock)->toBe(7);
});

it('shows the seller sales section on the sales page', function () {
    $user = User::factory()->create([
        'name' => 'Vendedor',
        'email' => 'vendedor@test.com',
        'email_verified_at' => now(),
    ]);

    $rol = Rol::create(['descripcion' => 'Vendedor']);

    $empleado = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $rol->idrol,
        'nombreCompleto' => 'Carlos Vendedor',
        'estado' => 'Activo',
    ]);

    Venta::create([
        'emision' => now()->toDateString(),
        'fechapago' => now()->toDateString(),
        'total' => 150.00,
        'estado' => 'Pagada',
        'descuento' => 0,
        'empleado_id' => $empleado->idempleado,
    ]);

    $this->actingAs($user)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Mis asignaciones')
        ->assertSee('Todavía no tienes asignaciones.')
        ->assertDontSee('Nueva venta');
});

it('blocks sellers from creating sales instead of closing their assignments', function () {
    $user = User::factory()->create([
        'name' => 'Vendedor',
        'email' => 'vendedor-precio@test.com',
        'email_verified_at' => now(),
    ]);

    $rol = Rol::create(['descripcion' => 'Vendedor']);

    $empleado = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $rol->idrol,
        'nombreCompleto' => 'Luis Vendedor',
        'estado' => 'Activo',
    ]);

    $receta = Receta::create([
        'nombre' => 'Receta de lata',
        'descripcion' => 'Receta base para la lata.',
        'instructivo' => 'Preparar y hornear.',
    ]);

    $producto = Producto::create([
        'nombre' => 'Lata de pan',
        'estado' => 'available',
        'stock' => 20,
        'foto' => '',
        'descripcion' => 'Lata de panes pequeños.',
        'precio' => 1.50,
        'costo' => 1.00,
        'receta_id' => $receta->idreceta,
    ]);

    $this->actingAs($user)
        ->post(route('ventas.store'), [
            'emision' => now()->toDateString(),
            'descuento' => 0,
            'empleado_id' => $empleado->idempleado,
            'incidencia' => 'Faltó producto al entregar.',
            'items' => [
                ['producto_id' => $producto->idproducto, 'cantidad' => 2],
            ],
        ])
        ->assertForbidden();

    expect(Venta::query()->count())->toBe(0);
});

it('shows seller assignment instead of incidence for a non-seller', function () {
    $user = User::factory()->create([
        'name' => 'Administrador',
        'email' => 'administrador-ventas@test.com',
        'email_verified_at' => now(),
    ]);

    $rol = Rol::create(['descripcion' => 'Administrador']);

    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $rol->idrol,
        'nombreCompleto' => 'Ana Administradora',
        'estado' => 'Activo',
    ]);

    $this->actingAs($user)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Asignar a vendedor')
        ->assertDontSee('Incidencia (opcional)');
});

it('uses the selected seller from the sale form when registering a sale', function () {
    $user = User::factory()->create([
        'name' => 'Administrador',
        'email' => 'admin@test.com',
        'email_verified_at' => now(),
    ]);

    $sellerUser = User::factory()->create([
        'name' => 'Pedro Vendedor',
        'email' => 'pedro-vendedor@test.com',
        'email_verified_at' => now(),
    ]);

    $rolVendedor = Rol::create(['descripcion' => 'Vendedor']);
    $rolAdmin = Rol::create(['descripcion' => 'Administrador']);

    $vendedor = Empleado::create([
        'idusuario' => $sellerUser->id,
        'idrol' => $rolVendedor->idrol,
        'nombreCompleto' => 'Pedro Vendedor',
        'estado' => 'Activo',
    ]);

    $adminEmpleado = Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $rolAdmin->idrol,
        'nombreCompleto' => 'Ana Administradora',
        'estado' => 'Activo',
    ]);

    $receta = Receta::create([
        'nombre' => 'Receta de lata',
        'descripcion' => 'Receta base para la lata.',
        'instructivo' => 'Preparar y hornear.',
    ]);

    $producto = Producto::create([
        'nombre' => 'Lata de pan',
        'estado' => 'available',
        'stock' => 20,
        'foto' => '',
        'descripcion' => 'Lata de panes pequeños.',
        'precio' => 1.50,
        'costo' => 1.00,
        'receta_id' => $receta->idreceta,
    ]);

    $this->actingAs($user)
        ->post(route('ventas.store'), [
            'emision' => now()->toDateString(),
            'descuento' => 15,
            'es_vendedor' => 1,
            'empleado_id' => $vendedor->idempleado,
            'items' => [
                ['producto_id' => $producto->idproducto, 'cantidad' => 2],
            ],
        ])
        ->assertRedirect(route('ventas.index'));

    $venta = Venta::query()->latest('idventa')->first();
    $asignacion = Ventaambulante::query()->first();

    expect((float) $venta->total)->toBe(2.00)
        ->and($venta->descuento)->toBe(0)
        ->and($venta->estado)->toBe('Pendiente')
        ->and($venta->fechapago)->toBeNull()
        ->and($venta->latas)->toBe(0)
        ->and($venta->empleado_id)->toBe($vendedor->idempleado)
        ->and($venta->empleado_id)->not->toBe($adminEmpleado->idempleado)
        ->and($asignacion->empleado_id)->toBe($vendedor->idempleado)
        ->and($asignacion->unidadesemitidas)->toBe(2);

    $this->actingAs($user)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Venta local (0)')
        ->assertSee('Venta ambulante (1)')
        ->assertSee('Pedro Vendedor')
        ->assertSee('$2.00')
        ->assertSee('id="sale-money"', false)
        ->assertDontSee('sale-latas')
        ->assertDontSee('sale-budget-result')
        ->assertSee('data-seller-sales');
});

it('shows only a sellers own assignments and lets them close with an optional incident', function () {
    $sellerUser = User::factory()->create(['email_verified_at' => now()]);
    $otherSellerUser = User::factory()->create(['email_verified_at' => now()]);
    $sellerRole = Rol::query()->where('descripcion', 'Vendedor')->firstOrFail();
    $seller = Empleado::create([
        'idusuario' => $sellerUser->id,
        'idrol' => $sellerRole->idrol,
        'nombreCompleto' => 'Vendedor asignado',
        'estado' => 'Activo',
    ]);
    $otherSeller = Empleado::create([
        'idusuario' => $otherSellerUser->id,
        'idrol' => $sellerRole->idrol,
        'nombreCompleto' => 'Otro vendedor',
        'estado' => 'Activo',
    ]);
    $assignment = Ventaambulante::create([
        'empleado_id' => $seller->idempleado,
        'unidadesemitidas' => 24,
    ]);
    $sale = Venta::create([
        'emision' => now()->toDateString(),
        'fechapago' => null,
        'total' => 6.84,
        'estado' => 'Pendiente',
        'descuento' => 0,
        'empleado_id' => $seller->idempleado,
        'ventaambulante_id' => $assignment->idventaambulante,
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta de pan',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $longBread = Producto::create([
        'nombre' => 'Pan largo',
        'estado' => 'available',
        'stock' => 100,
        'foto' => '',
        'descripcion' => 'Pan largo de prueba.',
        'precio' => 0.07,
        'costo' => 0.04,
        'receta_id' => $recipe->idreceta,
    ]);
    $roundBread = Producto::create([
        'nombre' => 'Pan redondo',
        'estado' => 'available',
        'stock' => 100,
        'foto' => '',
        'descripcion' => 'Pan redondo de prueba.',
        'precio' => 0.50,
        'costo' => 0.25,
        'receta_id' => $recipe->idreceta,
    ]);
    $longLine = $sale->detalleproductos()->create([
        'producto_id' => $longBread->idproducto,
        'cantidad' => 12,
        'subtotal' => 0.84,
    ]);
    $roundLine = $sale->detalleproductos()->create([
        'producto_id' => $roundBread->idproducto,
        'cantidad' => 12,
        'subtotal' => 6.00,
    ]);
    Ventaambulante::create([
        'empleado_id' => $otherSeller->idempleado,
        'unidadesemitidas' => 48,
    ]);

    $this->actingAs($sellerUser)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('24')
        ->assertSee('Pan largo')
        ->assertSee('Pan redondo')
        ->assertSee("productos[{$longLine->iddetalleproducto}][unidadesdevueltas]")
        ->assertSee("productos[{$roundLine->iddetalleproducto}][unidadesperdidas]")
        ->assertDontSee('<strong>48</strong>');

    $this->actingAs($sellerUser)
        ->getJson(route('api.ventas.asignaciones'))
        ->assertOk()
        ->assertJsonCount(1, 'asignaciones')
        ->assertJsonPath('asignaciones.0.id', $assignment->idventaambulante)
        ->assertJsonFragment(['nombre' => 'Pan largo', 'asignadas' => 12]);

    $this->actingAs($sellerUser)
        ->post(route('ventas.asignaciones.cerrar', $assignment->idventaambulante), [
            'productos' => [
                $longLine->iddetalleproducto => [
                    'unidadesdevueltas' => 5,
                    'unidadesperdidas' => 0,
                ],
                $roundLine->iddetalleproducto => [
                    'unidadesdevueltas' => 0,
                    'unidadesperdidas' => 2,
                ],
            ],
            'incidencia' => 'Se dañó una bolsa durante el recorrido.',
            'gravedad' => 'Baja',
        ])
        ->assertRedirect(route('ventas.index'));

    expect($assignment->fresh()->unidadesdevueltas)->toBe(5)
        ->and($assignment->fresh()->unidadesperdidas)->toBe(2)
        ->and($longLine->fresh()->unidadesdevueltas)->toBe(5)
        ->and($longLine->fresh()->unidadesperdidas)->toBe(0)
        ->and($roundLine->fresh()->unidadesdevueltas)->toBe(0)
        ->and($roundLine->fresh()->unidadesperdidas)->toBe(2)
        ->and((float) $sale->fresh()->total)->toBe(5.49)
        ->and($assignment->incidencias()->first()->detalle)->toBe('Se dañó una bolsa durante el recorrido.');

    $this->actingAs($sellerUser)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Cierre realizado')
        ->assertSee('Pan largo')
        ->assertSee('Vendidas 7')
        ->assertSee('Devueltas 5')
        ->assertSee('Pan redondo')
        ->assertSee('Vendidas 10')
        ->assertSee('Perdidas 2')
        ->assertDontSee('Pendiente de cierre');

    $adminUser = User::factory()->create(['email_verified_at' => now()]);
    $adminRole = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $adminUser->id,
        'idrol' => $adminRole->idrol,
        'nombreCompleto' => 'Administradora',
        'estado' => 'Activo',
    ]);

    $this->actingAs($adminUser)
        ->getJson(route('api.ventas.asignaciones'))
        ->assertOk()
        ->assertJsonCount(2, 'asignaciones')
        ->assertJsonPath('asignaciones.0.cerrada', true);

    $this->actingAs($adminUser)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Resumen de ventas por vendedor')
        ->assertSee('$5.49')
        ->assertSee('17')
        ->assertSee('data-seller-sales');
});

it('settles seller sales on close and returns returned units to stock', function () {
    $sellerUser = User::factory()->create(['email_verified_at' => now()]);
    $sellerRole = Rol::create(['descripcion' => 'Vendedor']);
    $seller = Empleado::create([
        'idusuario' => $sellerUser->id,
        'idrol' => $sellerRole->idrol,
        'nombreCompleto' => 'Vendedor de liquidación',
        'estado' => 'Activo',
    ]);
    $assignment = Ventaambulante::create([
        'empleado_id' => $seller->idempleado,
        'unidadesemitidas' => 24,
    ]);
    $sale = Venta::create([
        'emision' => today()->toDateString(),
        'fechapago' => null,
        'total' => 6.84,
        'estado' => 'Pendiente',
        'descuento' => 0,
        'empleado_id' => $seller->idempleado,
        'ventaambulante_id' => $assignment->idventaambulante,
    ]);
    $recipe = Receta::create([
        'nombre' => 'Receta para liquidación ambulante',
        'descripcion' => 'Receta de prueba.',
        'instructivo' => 'Preparar.',
    ]);
    $returnedProduct = Producto::create([
        'nombre' => 'Producto devuelto',
        'estado' => 'available',
        'visible' => true,
        'stock' => 88,
        'foto' => '',
        'descripcion' => 'Producto devuelto al cierre.',
        'precio' => 0.07,
        'costo' => 0.04,
        'receta_id' => $recipe->idreceta,
    ]);
    $lostProduct = Producto::create([
        'nombre' => 'Producto perdido',
        'estado' => 'available',
        'visible' => true,
        'stock' => 88,
        'foto' => '',
        'descripcion' => 'Producto perdido al cierre.',
        'precio' => 0.50,
        'costo' => 0.25,
        'receta_id' => $recipe->idreceta,
    ]);
    $returnedLine = $sale->detalleproductos()->create([
        'producto_id' => $returnedProduct->idproducto,
        'cantidad' => 12,
        'subtotal' => 0.84,
    ]);
    $lostLine = $sale->detalleproductos()->create([
        'producto_id' => $lostProduct->idproducto,
        'cantidad' => 12,
        'subtotal' => 6.00,
    ]);

    $this->actingAs($sellerUser)
        ->post(route('ventas.asignaciones.cerrar', $assignment->idventaambulante), [
            'productos' => [
                $returnedLine->iddetalleproducto => ['unidadesdevueltas' => 5, 'unidadesperdidas' => 0],
                $lostLine->iddetalleproducto => ['unidadesdevueltas' => 0, 'unidadesperdidas' => 2],
            ],
        ])
        ->assertRedirect(route('ventas.index'));

    $sale->refresh();
    expect((float) $sale->total)->toBe(5.49)
        ->and($sale->estado)->toBe('Pagada')
        ->and($sale->fechapago)->toBe(today()->toDateString())
        ->and((int) $returnedProduct->fresh()->stock)->toBe(88)
        ->and((int) $lostProduct->fresh()->stock)->toBe(88);

    $this->assertDatabaseHas('abonos', [
        'venta_id' => $sale->idventa,
        'cantidadabonada' => 549,
        'metodopago' => 'Sin especificar',
    ]);

    $adminUser = User::factory()->create(['email_verified_at' => now()]);
    $adminRole = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $adminUser->id,
        'idrol' => $adminRole->idrol,
        'nombreCompleto' => 'Administrador de liquidación',
        'estado' => 'Activo',
    ]);

    $this->actingAs($adminUser)
        ->get(route('mermas.index'))
        ->assertOk()
        ->assertSee('Pérdida de venta')
        ->assertSee('Devuelto')
        ->assertSee('Producto perdido');

    $this->actingAs($adminUser)
        ->getJson(route('totalPorCobrar'))
        ->assertOk()
        ->assertJsonPath('response.ventas_ambulantes', 0);
});

it('closes legacy seller returns without adding returned units to stock', function () {
    $sellerUser = User::factory()->create(['email_verified_at' => now()]);
    $sellerRole = Rol::create(['descripcion' => 'Vendedor']);
    $seller = Empleado::create([
        'idusuario' => $sellerUser->id,
        'idrol' => $sellerRole->idrol,
        'nombreCompleto' => 'Vendedor sin desglose',
        'estado' => 'Activo',
    ]);
    $assignment = Ventaambulante::create([
        'empleado_id' => $seller->idempleado,
        'unidadesemitidas' => 5,
    ]);
    $sale = Venta::create([
        'emision' => today()->toDateString(),
        'fechapago' => null,
        'total' => 2.50,
        'estado' => 'Pendiente',
        'descuento' => 0,
        'empleado_id' => $seller->idempleado,
        'ventaambulante_id' => $assignment->idventaambulante,
    ]);

    $this->actingAs($sellerUser)
        ->from(route('ventas.index'))
        ->post(route('ventas.asignaciones.cerrar', $assignment->idventaambulante), [
            'unidadesdevueltas' => 2,
            'unidadesperdidas' => 0,
        ])
        ->assertRedirect(route('ventas.index'));

    expect($assignment->fresh()->unidadesdevueltas)->toBe(2)
        ->and((float) $sale->fresh()->total)->toBe(1.5)
        ->and($sale->fresh()->estado)->toBe('Pagada')
        ->and($sale->abonos()->sum('cantidadabonada'))->toBe(150);
});
