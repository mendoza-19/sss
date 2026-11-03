<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Producto;

class indirectCost extends Model
{
    protected $primaryKey = 'idindirect_cost';
    protected $fillable = [
        'reason',
        'value',
        'update_at',
        'create_at',
        'productos_id'
    ];

    public function producto(): BelongsTo{
        return $this->belongsTo(Producto::class, 'productos_id','idproducto');
    }
}
