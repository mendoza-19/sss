<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('EmpleadoSeeder solo puede ejecutarse fuera de producción.');
        }

        $roles = [
            ['id' => 1, 'prefijoAnterior' => 'administrador', 'cuentas' => [
                ['nombre' => 'María José Hernández', 'correo' => 'mariajose.hernandez@gmail.com'],
                ['nombre' => 'Carlos Ernesto López', 'correo' => 'carlos.ernesto.lopez@gmail.com'],
            ]],
            ['id' => 2, 'prefijoAnterior' => 'vendedor', 'cuentas' => [
                ['nombre' => 'Ana Patricia Martínez', 'correo' => 'anapatricia.martinez@gmail.com'],
                ['nombre' => 'José Miguel Flores', 'correo' => 'josemiguel.flores@gmail.com'],
                ['nombre' => 'Karla Beatriz Ramírez', 'correo' => 'karlabeatriz.ramirez@gmail.com'],
                ['nombre' => 'Óscar Antonio Vásquez', 'correo' => 'oscarantonio.vasquez@gmail.com'],
            ]],
            ['id' => 3, 'prefijoAnterior' => 'panadero', 'cuentas' => [
                ['nombre' => 'Juan Carlos Molina', 'correo' => 'juancarlos.molina@gmail.com'],
                ['nombre' => 'Rosa Emilia Guardado', 'correo' => 'rosaemilia.guardado@gmail.com'],
                ['nombre' => 'Kevin Alexander Rivera', 'correo' => 'kevin.alexander.rivera@gmail.com'],
                ['nombre' => 'Teresa de Jesús Portillo', 'correo' => 'teresa.portillo@gmail.com'],
            ]],
            ['id' => 4, 'prefijoAnterior' => 'repartidor', 'cuentas' => [
                ['nombre' => 'Luis Alberto Pineda', 'correo' => 'luisalberto.pineda@gmail.com'],
                ['nombre' => 'Cecilia Maribel Menjívar', 'correo' => 'cecilia.maribel.menjivar@gmail.com'],
                ['nombre' => 'Mario Armando Escobar', 'correo' => 'mario.armando.escobar@gmail.com'],
                ['nombre' => 'Ernesto Salvador Chacón', 'correo' => 'ernesto.salvador.chacon@gmail.com'],
            ]],
        ];

        foreach ($roles as $rol) {
            foreach ($rol['cuentas'] as $indice => $cuenta) {
                $correoAnterior = $rol['prefijoAnterior'] . ($indice + 1) . '@panaderia.test';
                $usuarioAnterior = User::where('email', $correoAnterior)->first();
                $usuarioConCorreo = User::where('email', $cuenta['correo'])->first();

                if ($usuarioAnterior && $usuarioConCorreo && $usuarioAnterior->id !== $usuarioConCorreo->id) {
                    throw new RuntimeException('El correo ' . $cuenta['correo'] . ' ya pertenece a otra cuenta.');
                }

                if ($usuarioConCorreo && !$usuarioAnterior) {
                    $empleadoExistente = Empleado::where('idusuario', $usuarioConCorreo->id)->first();
                    $esCuentaSemilla = $empleadoExistente
                        && (int) $empleadoExistente->idrol === $rol['id']
                        && $empleadoExistente->nombreCompleto === $cuenta['nombre'];

                    if (!$esCuentaSemilla) {
                        throw new RuntimeException('El correo ' . $cuenta['correo'] . ' ya pertenece a otra cuenta.');
                    }
                }

                $usuario = $usuarioConCorreo ?? $usuarioAnterior ?? new User();

                $usuario->forceFill([
                    'name' => $cuenta['nombre'],
                    'email' => $cuenta['correo'],
                    'password' => 'Admin123@',
                    'email_verified_at' => now(),
                    'iscustomer' => false,
                ])->save();

                Empleado::updateOrCreate(
                    ['idusuario' => $usuario->id],
                    [
                        'idrol' => $rol['id'],
                        'nombreCompleto' => $cuenta['nombre'],
                        'estado' => 'Activo',
                    ]
                );
            }
        }
    }
}