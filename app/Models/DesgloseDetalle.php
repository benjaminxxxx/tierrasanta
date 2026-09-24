<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesgloseDetalle extends Model
{
    use HasFactory;

    protected $table = 'desglose_detalles';

    protected $fillable = [
        'desglose_id',
        'nro_documento',
        'referencia_nro_caja',
        'razon_social',
        'tipo_gasto',
        'descripcion',
        'monto',
        'saldo_resultante',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_resultante' => 'decimal:2',
    ];

    /**
     * El detalle pertenece a un desglose cabecera.
     */
    public function desglose(): BelongsTo
    {
        return $this->belongsTo(Desglose::class, 'desglose_id');
    }

    public function gastosAdicionales(): HasMany
    {
        return $this->hasMany(GastoAdicionalPorGrupoCuadrilla::class, 'desglose_detalle_id');
    }

    /**
     * Registros diarios de cuadrilla asociados a este pago.
     */
    public function registrosDiarios(): HasMany
    {
        return $this->hasMany(CuadRegistroDiario::class, 'desglose_detalle_id');
    }
    public function actividadesBonos(): HasMany
    {
        return $this->hasMany(CuadActividadBono::class, 'desglose_detalle_id');
    }
    /**
     * Bonos individuales de actividades asociados a este pago.
     */
    public function bonosActividades(): HasMany
    {
        return $this->hasMany(CuadActividadBono::class, 'desglose_detalle_id');
    }
}