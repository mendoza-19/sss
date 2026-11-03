<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    //protected $table = 'incidencia';
    protected $primaryKey = 'idincidencia';
    protected $fillable = [
        'fecha',
        'hora',
        'detalle',
        'gravedad',
        'pedido_id',
        'ventaambulante_id',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function ventaambulante(): BelongsTo
    {
        return $this->belongsTo(Ventaambulante::class);
    }
}
