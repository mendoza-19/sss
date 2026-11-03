<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $proveedorId = DB::table('proveedors')
                ->where('nombre', 'Suministros La Espiga')
                ->value('idproveedor');

            if (! $proveedorId) {
                throw new RuntimeException('No se encontró el proveedor inicial para el recibo de ingredientes.');
            }

            $compras = [
                ['nombre' => 'Harina', 'cantidad' => 50, 'valorunitario' => 0.40],
                ['nombre' => 'Sal', 'cantidad' => 15, 'valorunitario' => 0.10],
                ['nombre' => 'Levadura', 'cantidad' => 15, 'valorunitario' => 1.50],
                ['nombre' => 'Manteca', 'cantidad' => 50, 'valorunitario' => 1.10],
            ];
            $fecha = now()->toDateString();
            $total = collect($compras)->sum(
                fn ($compra) => $compra['cantidad'] * $compra['valorunitario']
            );

            $facturaId = DB::table('facturas')->insertGetId([
                'emicion' => $fecha,
                'fechapago' => $fecha,
                'total' => round($total, 2),
                'foto' => null,
                'proveedor_id' => $proveedorId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'idfactura');

            foreach ($compras as $compra) {
                $ingrediente = DB::table('ingredientes')
                    ->whereRaw('LOWER(nombre) = ?', [strtolower($compra['nombre'])])
                    ->first();

                if (! $ingrediente) {
                    throw new RuntimeException("No se encontró el ingrediente {$compra['nombre']} para el recibo inicial.");
                }

                DB::table('detallefacturas')->insert([
                    'cantidad' => $compra['cantidad'],
                    'valorunitario' => $compra['valorunitario'],
                    'fechavencimiento' => now()->addMonths(6)->toDateString(),
                    'factura_id' => $facturaId,
                    'ingrediente_id' => $ingrediente->idingrediente,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('ingredientes')
                    ->where('idingrediente', $ingrediente->idingrediente)
                    ->increment('stock', $compra['cantidad'], ['updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
    }
};