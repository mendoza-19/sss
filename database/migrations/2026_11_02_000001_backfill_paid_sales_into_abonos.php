<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ventas')
            ->whereNotNull('fechapago')
            ->orderBy('idventa')
            ->chunkById(100, function ($ventas) {
                foreach ($ventas as $venta) {
                    $totalEnCentavos = (int) round((float) $venta->total * 100);
                    $abonadoEnCentavos = (int) DB::table('abonos')
                        ->where('venta_id', $venta->idventa)
                        ->sum('cantidadabonada');
                    $pendienteEnCentavos = max(0, $totalEnCentavos - $abonadoEnCentavos);

                    if ($pendienteEnCentavos === 0) {
                        continue;
                    }

                    $fechaPago = $venta->fechapago . ' 00:00:00';
                    DB::table('abonos')->insert([
                        'fechaabono' => $venta->fechapago,
                        'cantidadabonada' => $pendienteEnCentavos,
                        'metodopago' => 'Pago histórico',
                        'venta_id' => $venta->idventa,
                        'created_at' => $fechaPago,
                        'updated_at' => $fechaPago,
                    ]);
                }
            }, 'idventa', 'idventa');
    }

    public function down(): void
    {
        DB::table('abonos')->where('metodopago', 'Pago histórico')->delete();
    }
};