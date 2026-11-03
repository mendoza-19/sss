<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oferta extends Model
{
    //protected $table = "oferta";
    protected $primaryKey = 'idoferta';

    protected $fillable = [
        'descuento',
        'fechainicio',
        'fechafin',
        'cliente_id',
        'producto',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'idcliente');
    }

    public function clientes(): BelongsTo
    {
        return $this->cliente();
    }

    public function productoRel(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto', 'idproducto');
    }

    public function productos(): BelongsTo
    {
        return $this->productoRel();
    }
}
