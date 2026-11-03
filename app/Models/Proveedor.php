<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    //protected $table = 'proveedor';
    protected $primaryKey = 'idproveedor';

    protected $fillable = [
        'nombre',
        'telefono',
        'correo',
        'descripcion',
        'puntuacion',
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }
}
