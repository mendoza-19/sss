<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proporcionesreceta extends Model
{
    use HasFactory;

    protected $table = 'proporcionesrecetas';

    protected $primaryKey = 'idproporciones';

    protected $fillable = [
        'unidades',
        'medida',
        'preparacion_id',
        'ingrediente_id',
    ];

    /**
     * Preparación a la que pertenece esta proporción.
     */
    public function preparacion(): BelongsTo
    {
        return $this->belongsTo(
            Preparacion::class,
            'preparacion_id',
            'id'
        );
    }

    /**
     * Ingrediente utilizado en la preparación.
     */
    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(
            Ingrediente::class,
            'ingrediente_id',
            'idingrediente'
        );
    }
}
