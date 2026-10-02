<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movimiento de caja (una fila de la hoja BASE). importe en soles con signo: + ingreso, − egreso.
 * El disponible no se guarda: es el acumulado ordenado por fecha y orden (CajaMovimientoConsulta).
 */
class CajaMovimiento extends Model
{
    use SoftDeletes;

    protected $table = 'caja_movimientos';

    protected $fillable = [
        'empresa', 'numero_caja', 'es_contable', 'condicion', 'categoria', 'codigo', 'beneficiario',
        'descripcion', 'caja_clasificador_id', 'clasificador_1', 'clasificador_2', 'subgrupo_ng', 'subgrupo_bl',
        'moneda', 'fecha', 'semana', 'tipo_documento', 'numero_documento', 'situacion_cheque',
        'importe_usd', 'tipo_cambio_operacion', 'importe', 'importe_detalle', 'tipo_cambio',
        'color_fondo', 'color_texto', 'negrita', 'orden', 'creado_por', 'actualizado_por', 'eliminado_por', 'motivo_eliminacion',
        'es_saldo_inicial', 'caja_oficina_movimiento_id', 'editado_manual',
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_contable' => 'boolean',
        'es_saldo_inicial' => 'boolean',
        'editado_manual' => 'boolean',
        'negrita' => 'boolean',
        'importe' => 'decimal:2',
        'importe_usd' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'tipo_cambio_operacion' => 'decimal:4',
    ];

    public function clasificador()
    {
        return $this->belongsTo(CajaClasificador::class, 'caja_clasificador_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    public function getTipoAttribute(): string
    {
        return (float) $this->importe >= 0 ? 'INGRESO' : 'EGRESO';
    }
}
