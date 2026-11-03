<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abono extends Model
{
    //protected $table = 'abonos';
    protected $primaryKey = 'idabono';
    protected $fillable = [
        'fechaabono',
        'cantidadabonada',
        'metodopago',
    ];

    public function ventas(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'idventa');
    }
}
