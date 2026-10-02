<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movimiento de la caja de oficina: el dinero físico que pasa por la oficina. importe en soles con signo.
 * Una fila con inverso_de_id anula a otra (el dinero no entró realmente): las dos suman cero.
 */
class CajaOficinaMovimiento extends Model
{
    use SoftDeletes;

    /** Lo que viaja a la caja de movimientos (los clasificadores y sub-grupos se ponen allá). */
    public const CAMPOS_ENVIO = [
        'numero_caja', 'es_contable', 'condicion', 'categoria', 'codigo', 'beneficiario', 'descripcion', 'fecha', 'semana',
        'tipo_documento', 'numero_documento', 'situacion_cheque', 'importe_usd', 'tipo_cambio_operacion', 'importe',
        'importe_detalle', 'tipo_cambio', 'es_saldo_inicial',
    ];

    protected $table = 'caja_oficina_movimientos';

    protected $fillable = [
        'empresa', 'numero_caja', 'es_contable', 'condicion', 'categoria', 'codigo', 'beneficiario', 'descripcion',
        'moneda', 'fecha', 'semana', 'tipo_documento', 'numero_documento', 'situacion_cheque',
        'importe_usd', 'tipo_cambio_operacion', 'importe', 'importe_detalle', 'tipo_cambio', 'es_saldo_inicial',
        'inverso_de_id', 'orden', 'pendiente_envio', 'enviado_at', 'ultimo_enviado',
        'creado_por', 'actualizado_por', 'eliminado_por', 'motivo_eliminacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_contable' => 'boolean',
        'es_saldo_inicial' => 'boolean',
        'pendiente_envio' => 'boolean',
        'enviado_at' => 'datetime',
        'ultimo_enviado' => 'array',
        'importe' => 'decimal:2',
        'importe_usd' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
        'tipo_cambio_operacion' => 'decimal:4',
    ];

    public function inversoDe()
    {
        return $this->belongsTo(self::class, 'inverso_de_id');
    }

    public function inverso()
    {
        return $this->hasOne(self::class, 'inverso_de_id');
    }

    /** Los datos que se envían, en una forma comparable (fecha Y-m-d, números como float). */
    public function datosEnvio(): array
    {
        $numero = fn($v) => $v === null ? null : (float) $v;
        return [
            'numero_caja' => $this->numero_caja !== null ? (int) $this->numero_caja : null,
            'es_contable' => (bool) $this->es_contable,
            'condicion' => $this->condicion,
            'categoria' => $this->categoria,
            'codigo' => $this->codigo,
            'beneficiario' => $this->beneficiario,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha->toDateString(),
            'semana' => (int) $this->semana,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'situacion_cheque' => $this->situacion_cheque,
            'importe_usd' => $numero($this->importe_usd),
            'tipo_cambio_operacion' => $numero($this->tipo_cambio_operacion),
            'importe' => (float) $this->importe,
            'importe_detalle' => $this->importe_detalle,
            'tipo_cambio' => $numero($this->tipo_cambio),
            'es_saldo_inicial' => (bool) $this->es_saldo_inicial,
        ];
    }
}
