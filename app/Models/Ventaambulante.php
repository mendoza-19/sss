<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ventaambulante extends Model
{
    protected $primaryKey = 'idventaambulante';
    protected $fillable = [
        'empleado_id',
        'unidadesemitidas',
        'unidadesdevueltas',
        'unidadesperdidas',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'idempleado');
    }

    public function venta(): HasOne
    {
        return $this->hasOne(Venta::class, 'ventaambulante_id', 'idventaambulante');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'ventaambulante_id', 'idventaambulante');
    }
}
