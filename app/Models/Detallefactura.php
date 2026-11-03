<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detallefactura extends Model
{
    //protected $table = 'detallefactura';
    protected $primaryKey = 'iddetallefactura';

    protected $fillable = [
        'cantidad',
        'valorunitario',
        'fechavencimiento',
        'factura_id',
        'ingrediente_id',
    ];

    public function facturas(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id', 'idfactura');
    }

    public function ingredientes(): BelongsTo
    {
        return $this->belongsTo(Ingrediente::class, 'ingrediente_id', 'idingrediente');
    }
}
