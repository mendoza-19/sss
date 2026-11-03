<?php

use App\Models\Empleado;
use App\Models\GastoMensual;
use App\Models\Rol;
use App\Models\User;

it('registra y muestra los gastos agrupados por mes y categoría', function () {
    $user = User::factory()->create([
        'email' => 'admin-gastos@test.com',
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => 'Administrador']);
    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => 'Administrador de gastos',
        'estado' => 'Activo',
    ]);

    $this->actingAs($user)
        ->postJson(route('gastos.store'), [
            'categoria' => 'agua',
            'fecha' => '2026-11-02',
            'descripcion' => 'Factura de noviembre',
            'monto' => '24.50',
        ])
        ->assertCreated()
        ->assertJsonPath('gasto.mes', '2026-11')
        ->assertJsonPath('gasto.fecha', '2026-11-02');

    $this->assertDatabaseHas('gastos_mensuales', [
        'categoria' => 'agua',
        'descripcion' => 'Factura de noviembre',
    ]);
    $gasto = GastoMensual::where('categoria', 'agua')->firstOrFail();
    expect($gasto->mes->toDateString())->toBe('2026-11-01')
        ->and($gasto->fecha->toDateString())->toBe('2026-11-02')
        ->and((float) $gasto->monto)->toBe(24.5);

    $this->actingAs($user)
        ->postJson(route('gastos.store'), [
            'categoria' => 'gas',
            'fecha' => '2026-11-03',
            'descripcion' => 'Primera recarga',
            'monto' => 40,
        ])
        ->assertCreated()
        ->assertJsonPath('gasto.fecha', '2026-11-03');

    $this->actingAs($user)
        ->postJson(route('gastos.store'), [
            'categoria' => 'gas',
            'fecha' => '2026-11-05',
            'descripcion' => 'Segunda recarga',
            'monto' => 40,
        ])
        ->assertCreated()
        ->assertJsonPath('gasto.fecha', '2026-11-05');

    GastoMensual::create([
        'categoria' => 'gas',
        'mes' => '2026-10-01',
        'fecha' => '2026-10-01',
        'descripcion' => 'Factura de octubre',
        'monto' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('gastos.index', ['mes' => '2026-11']))
        ->assertOk()
        ->assertSee('Gastos del mes')
        ->assertSee('Factura de noviembre')
        ->assertSee('$24.50')
        ->assertSee('Primera recarga')
        ->assertSee('Segunda recarga')
        ->assertSee('$104.50')
        ->assertDontSee('Factura de octubre');

    $this->actingAs($user)
        ->delete(route('gastos.destroy', $gasto))
        ->assertRedirect(route('gastos.index', ['mes' => '2026-11']))
        ->assertSessionHas('success', 'Registro eliminado.');

    $this->assertDatabaseMissing('gastos_mensuales', ['id' => $gasto->id]);
});