<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingrediente extends Model
{
    //protected $table = 'ingrediente';
    protected $primaryKey = 'idingrediente';

    protected $fillable = [
        'nombre',
        'stock',
        'medida',
        'descripcion',
    ];

    public function recetas(): BelongsToMany
    {
        return $this->belongsToMany(Receta::class);
    }

    public function detallefacturas(): HasMany
    {
        return $this->hasMany(
            Detallefactura::class,
            'ingrediente_id',
            'idingrediente'
        );
    }
}
