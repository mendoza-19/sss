<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $primaryKey = 'idempleado';

    protected $fillable = [
        'idusuario',
        'idrol',
        'duiempleado',
        'nombreCompleto',
        'telefono',
        'iniciocontrato',
        'estado',
        'iddistrito',
        'Residencia',
        'foto',
    ];

    protected $casts = [
        'iniciocontrato' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'idrol', 'idrol');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class, 'iddistrito', 'iddistrito');
    }

    public function ventasambulantes(): HasMany
    {
        return $this->hasMany(Ventaambulante::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
