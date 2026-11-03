<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    //protected $table = "productos";
    protected $primaryKey = "idproducto";
    protected $fillable = [
        'nombre',
        'estado',
        'visible',
        'stock',
        'foto',
        'descripcion',
        'precio',
        'costo',
        'receta_id'
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function recetas(): BelongsTo
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'producto', 'idproducto');
    }

    public function detalleproductos(): HasMany
    {
        return $this->hasMany(
            Detalleproducto::class,
            'producto_id',
            'idproducto'
        );
    }

    public function indirectCost(): HasMany{
        return $this->hasMany(indirectCost::class,'productos_id','idproducto');
    }
}
