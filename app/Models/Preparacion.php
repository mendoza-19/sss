<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Preparacion extends Model
{
    use HasFactory;

    protected $table = 'preparaciones';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'receta_id',
        'fecha',
        'unidades_planeadas',
        'libras_planeadas',
        'latas_horneadas',
        'detalle_latas',
        'unidades_horneadas',
        'unidades_quemadas',
        'unidades_producidas',
        'costo_total',
    ];

    protected $casts = [
        'libras_planeadas' => 'decimal:3',
        'detalle_latas' => 'array',
    ];

    /**
     * Receta que se está preparando.
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
     * Ingredientes y unidades utilizados
     * en esta preparación.
     */
    public function proporciones(): HasMany
    {
        return $this->hasMany(
            Proporcionesreceta::class,
            'preparacion_id',
            'id'
        );
    }
}
