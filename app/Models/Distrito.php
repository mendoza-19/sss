<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distrito extends Model
{
    protected $table = 'distrito';

    protected $primaryKey = 'iddistrito';

    public $timestamps = false;

    protected $fillable = ['nombre', 'idmunicipio'];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'idmunicipio', 'idmunicipio');
    }
}