<?php

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\EmpleadoSeeder;
use Database\Seeders\RoleSeeder;

function createUserWithRoleForRouteAccessTest(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Rol::create(['descripcion' => $roleName]);

    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => $user->name,
        'estado' => 'Activo',
    ]);

    return $user;
}

it('allows sellers into sales and blocks recipes and providers by direct URL', function () {
    $seller = createUserWithRoleForRouteAccessTest('Vendedor');

    $this->actingAs($seller)->get(route('ventas.index'))->assertOk();
    $this->actingAs($seller)
        ->get(route('recetas.index'))
        ->assertForbidden()
        ->assertSee('Volver');
    $this->actingAs($seller)->get(route('Proveedores'))->assertForbidden();
    $this->actingAs($seller)->get(route('productos'))->assertForbidden();
    $this->actingAs($seller)->get(route('pedidos'))->assertForbidden();
    $this->actingAs($seller)->get(route('api.ofertas.list'))->assertForbidden();
});

it('allows bakers into recipes and blocks sales by direct URL', function () {
    $baker = createUserWithRoleForRouteAccessTest('Panadero');

    $this->actingAs($baker)
        ->get(route('recetas.index'))
        ->assertOk()
        ->assertDontSee('Nueva receta')
        ->assertDontSee('Editar receta')
        ->assertSee('id="modal-view-recipe"', false)
        ->assertSee('id="modal-process-recipe"', false);
    $this->actingAs($baker)->post(route('crearreceta'))->assertForbidden();
    $this->actingAs($baker)->get(route('ventas.index'))->assertForbidden();
});

it('allows bakers to view products without the edit modal', function () {
    $baker = createUserWithRoleForRouteAccessTest('Panadero');

    $this->actingAs($baker)
        ->get(route('productos'))
        ->assertOk()
        ->assertDontSee('id="product-modal"', false)
        ->assertSee('id="product-photo-modal"', false);
});

it('allows bakers to view ingredients without create or edit controls', function () {
    $baker = createUserWithRoleForRouteAccessTest('Panadero');

    $this->actingAs($baker)
        ->get(route('ingredientes.index'))
        ->assertOk()
        ->assertDontSee('Nuevo ingrediente')
        ->assertDontSee('id="modal-new-ingrediente"', false);

    $this->actingAs($baker)->post(route('api.ingredientes.crearIngrediente'))->assertForbidden();
    $this->actingAs($baker)->post(route('api.ingredientes.actualizarIngrediente'))->assertForbidden();
});

it('allows delivery workers into orders and blocks products by direct URL', function () {
    $delivery = createUserWithRoleForRouteAccessTest('Repartidor');

    $this->actingAs($delivery)->get(route('pedidos'))->assertOk();
    $this->actingAs($delivery)->get(route('productos'))->assertForbidden();
});

it('logs seeded employees into the dashboard instead of the customer store', function () {
    $this->seed([RoleSeeder::class, EmpleadoSeeder::class]);

    $response = $this->post(route('validar-login'), [
        'email' => 'anapatricia.martinez@gmail.com',
        'password' => 'Admin123@',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->get(route('dashboard'))->assertRedirect(route('ventas.index'));
    $this->get(route('ventas.index'))->assertOk()->assertSee('Mis asignaciones');
});