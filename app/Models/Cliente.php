<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    //protected $table = 'cliente';
    protected $primaryKey = 'idcliente';
    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'fecharegistro',
        'foto',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function users(): BelongsTo
    {
        return $this->user();
    }

    public function tarjetas(): HasMany
    {
        return $this->hasMany(Tarjeta::class, 'cliente_id', 'idcliente');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'cliente_id', 'idcliente');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'cliente_id', 'idcliente');
    }
}
