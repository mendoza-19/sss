<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $table = 'municipio';

    protected $primaryKey = 'idmunicipio';

    public $timestamps = false;

    protected $fillable = ['nombre', 'iddepartamento'];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'iddepartamento', 'iddepartamento');
    }

    public function distritos(): HasMany
    {
        return $this->hasMany(Distrito::class, 'idmunicipio', 'idmunicipio');
    }
}