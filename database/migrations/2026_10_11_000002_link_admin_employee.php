<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $usuario = DB::table('users')
            ->where('email', env('ADMIN_EMAIL'))
            ->first();

        if (! $usuario) {
            return;
        }

        $rolId = DB::table('rol')
            ->where('descripcion', 'Administrador')
            ->value('idrol');

        if (! $rolId) {
            throw new RuntimeException('No se encontró el rol Administrador.');
        }

        $empleado = DB::table('empleados')
            ->where('idusuario', $usuario->id)
            ->first();

        $datos = [
            'idrol' => $rolId,
            'estado' => 'Activo',
        ];

        $camposConfigurables = [
            'duiempleado' => 'ADMIN_EMPLOYEE_DUI',
            'nombreCompleto' => 'ADMIN_EMPLOYEE_NAME',
            'telefono' => 'ADMIN_EMPLOYEE_PHONE',
            'iniciocontrato' => 'ADMIN_EMPLOYEE_START_DATE',
            'Residencia' => 'ADMIN_EMPLOYEE_RESIDENCE',
        ];

        foreach ($camposConfigurables as $campo => $variable) {
            $valor = env($variable);

            if ($valor !== null && $valor !== '') {
                $datos[$campo] = $valor;
            }
        }

        $distritoId = env('ADMIN_EMPLOYEE_DISTRICT_ID');

        if ($distritoId !== null && $distritoId !== '') {
            if (! DB::table('distrito')->where('iddistrito', $distritoId)->exists()) {
                throw new RuntimeException('ADMIN_EMPLOYEE_DISTRICT_ID no corresponde a un distrito existente.');
            }

            $datos['iddistrito'] = (int) $distritoId;
        }

        if (! isset($datos['nombreCompleto']) && (! $empleado || ! $empleado->nombreCompleto)) {
            $datos['nombreCompleto'] = $usuario->name;
        }

        if (isset($datos['nombreCompleto'])) {
            DB::table('users')
                ->where('id', $usuario->id)
                ->update(['name' => $datos['nombreCompleto']]);
        }

        if ($empleado) {
            $datos['updated_at'] = now();

            DB::table('empleados')
                ->where('idempleado', $empleado->idempleado)
                ->update($datos);

            return;
        }

        DB::table('empleados')->insert(array_merge([
            'idusuario' => $usuario->id,
            'duiempleado' => null,
            'nombreCompleto' => $usuario->name,
            'telefono' => null,
            'iniciocontrato' => null,
            'iddistrito' => null,
            'Residencia' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $datos));
    }

    public function down(): void
    {
    }
};