<?php

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\User;

function createEmployeeUserForRoleAccessTest(string $roleName): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $role = Rol::create(['descripcion' => $roleName]);

    Empleado::create([
        'idusuario' => $user->id,
        'idrol' => $role->idrol,
        'nombreCompleto' => $user->name,
        'estado' => 'Activo',
    ]);

    return $user;
}

it('denies sellers direct access to employee pages and APIs', function () {
    $seller = createEmployeeUserForRoleAccessTest('Vendedor');

    $this->actingAs($seller)
        ->get(route('empleados.index'))
        ->assertForbidden();

    $this->actingAs($seller)
        ->post(route('api.empleados.getEmpleadosPagination'))
        ->assertForbidden();
});

it('allows administrators to access employee management', function () {
    $administrator = createEmployeeUserForRoleAccessTest('Administrador');

    $this->actingAs($administrator)
        ->get(route('empleados.index'))
        ->assertOk();
});