<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarjeta extends Model
{
    //protected $table = 'tarjeta';
    protected $primaryKey = 'idtarjeta';
    protected $fillable = [
        'codigo',
        'caducidad',
        'tipo',
        'clave',
    ];

    public function clientes(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
