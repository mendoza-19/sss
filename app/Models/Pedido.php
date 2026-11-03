<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    //protected $table = 'pedido';
    protected $primaryKey = 'idpedido';
    protected $fillable = [
        'direccion',
        'estado',
        'estado_produccion',
        'cliente_id',
        'repartidor_id',
        'tipo',
        'fecha_entrega',
        'estado_pago',
        'fechapago',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'fechapago' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'idcliente');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'repartidor_id', 'idempleado');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(Detalleproducto::class, 'pedido_id', 'idpedido');
    }

    public function detalleproductos(): HasMany
    {
        return $this->hasMany(Detalleproducto::class, 'pedido_id', 'idpedido');
    }
}
