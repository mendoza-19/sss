<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Receta extends Model
{
    protected $table = 'recetas';

    protected $primaryKey = 'idreceta';

    protected $fillable = [
        'nombre',
        'descripcion',
        'instructivo',
        'peso',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
    ];

    /**
     * Productos relacionados con la receta.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(
            Producto::class,
            'receta_id',
            'idreceta'
        );
    }

    /**
     * Ingredientes que pertenecen a la receta.
     * Relación mediante la tabla receta_ingrediente.
     */
    public function ingredientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'receta_ingrediente',
            'receta_id',
            'idingrediente',
            'idreceta',
            'idingrediente'
        )->withPivot(['peso', 'medida']);
    }

    /**
     * Preparaciones realizadas para esta receta.
     */
    public function preparaciones(): HasMany
    {
        return $this->hasMany(
            Preparacion::class,
            'receta_id',
            'idreceta'
        );
    }
}
