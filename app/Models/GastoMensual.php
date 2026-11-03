<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoMensual extends Model
{
    protected $table = 'gastos_mensuales';

    protected $fillable = [
        'categoria',
        'mes',
        'fecha',
        'descripcion',
        'monto',
    ];

    protected $casts = [
        'mes' => 'date',
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];
}