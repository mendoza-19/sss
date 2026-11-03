<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detalleproducto extends Model
{
    //protected $table = "detalleproductos";
    protected $primaryKey = 'iddetalleproducto';
    protected $fillable = [
        'cantidad',
        'subtotal',
        'unidadesdevueltas',
        'unidadesperdidas',
        'descuento_aplicado',
        'producto_id',
        'venta_id',
        'pedido_id',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'idproducto');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'idventa');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id', 'idpedido');
    }
}
