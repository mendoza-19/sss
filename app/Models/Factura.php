<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    //protected $table = 'factura';
    protected $primaryKey = 'idfactura';

    protected $fillable = [
        'emicion',
        'fechapago',
        'total',
        'foto',
        'proveedor_id',
    ];

    public function proveedors(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'idproveedor');
    }

    public function detallefacturas(): HasMany
    {
        return $this->hasMany(Detallefactura::class, 'factura_id', 'idfactura');
    }
}
