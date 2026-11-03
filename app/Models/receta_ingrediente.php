<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaIngrediente extends Model
{
    use HasFactory;

    protected $table = 'receta_ingrediente';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'receta_id',
        'idingrediente',
    ];

    /**
     * Receta a la que pertenece este registro.
     */
    public function receta(): BelongsTo
    {
        return $this->belongsTo(
            Receta::class,
            'receta_id',
            'idreceta'
        );
    }

    /**
     * Ingrediente asociado a la receta.
     */
    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(
            Ingrediente::class,
            'idingrediente',
            'idingrediente'
        );
    }
}
