<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pedidosPagados = DB::table('detalleproductos')
            ->join('ventas', 'ventas.idventa', '=', 'detalleproductos.venta_id')
            ->whereNotNull('detalleproductos.pedido_id')
            ->whereNotNull('ventas.fechapago')
            ->select('detalleproductos.pedido_id');

        DB::table('pedidos')
            ->whereIn('idpedido', $pedidosPagados)
            ->update([
                'estado_pago' => 'pagada',
                'fechapago' => DB::raw('(SELECT MAX(ventas.fechapago) FROM detalleproductos JOIN ventas ON ventas.idventa = detalleproductos.venta_id WHERE detalleproductos.pedido_id = pedidos.idpedido AND ventas.fechapago IS NOT NULL)'),
            ]);
    }

    public function down(): void
    {
    }
};