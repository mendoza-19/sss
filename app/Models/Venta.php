<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    //protected $table = 'venta';
    protected $primaryKey = 'idventa';
    protected $fillable = [
        'emision',
        'fechapago',
        'total',
        'estado',
        'descuento',
        'latas',
        'panes_equivalentes',
        'incidencia',
        'empleado_id',
        'ventaambulante_id',
    ];

    protected static function booted(): void
    {
        static::saved(function (Venta $venta) {
            if (!$venta->fechapago || strtolower(trim((string) $venta->estado)) !== 'pagada') {
                return;
            }

            $venta->registrarAbonoPendiente();
        });
    }

    public function registrarAbonoPendiente(string $metodoPago = 'Sin especificar'): void
    {
        $totalEnCentavos = (int) round((float) $this->total * 100);
        $abonadoEnCentavos = (int) $this->abonos()->sum('cantidadabonada');
        $pendienteEnCentavos = max(0, $totalEnCentavos - $abonadoEnCentavos);

        if ($pendienteEnCentavos === 0) {
            return;
        }

        $this->abonos()->create([
            'fechaabono' => $this->fechapago ?: today()->toDateString(),
            'cantidadabonada' => $pendienteEnCentavos,
            'metodopago' => $metodoPago,
        ]);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class, 'venta_id', 'idventa');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'idempleado');
    }

    public function detalleproductos(): HasMany
    {
        return $this->hasMany(
            Detalleproducto::class,
            'venta_id',
            'idventa'
        );
    }

    public function ventaambulante(): BelongsTo
    {
        return $this->belongsTo(Ventaambulante::class, 'ventaambulante_id', 'idventaambulante');
    }
}
