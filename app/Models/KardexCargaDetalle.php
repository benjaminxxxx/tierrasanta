<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una fila de la hoja INDICE del macro (= un kardex) y el resultado de su importación. */
class KardexCargaDetalle extends Model
{
    public const PENDIENTE = 'pendiente';
    public const EXITO = 'exito';
    public const ERROR = 'error';
    public const SIN_PRODUCTO = 'sin_producto';
    public const SIN_MOVIMIENTOS = 'sin_movimientos';

    protected $table = 'kardex_carga_detalles';

    protected $fillable = [
        'kardex_carga_id',
        'fila',
        'codigo_existencia',
        'nombre',
        'entradas_cantidad',
        'entradas_importe',
        'salidas_cantidad',
        'salidas_importe',
        'estado',
        'mensaje',
        'producto_id',
        'producto_nombre',
        'kardex_id',
        'kardex_creado',
        'compras',
        'salidas',
        'intentos',
        'version_archivo',
        'procesado_at',
    ];

    protected $casts = [
        'entradas_cantidad' => 'float',
        'entradas_importe' => 'float',
        'salidas_cantidad' => 'float',
        'salidas_importe' => 'float',
        'kardex_creado' => 'boolean',
        'procesado_at' => 'datetime',
    ];

    public function carga(): BelongsTo
    {
        return $this->belongsTo(KardexCarga::class, 'kardex_carga_id');
    }

    /** Se procesa: tiene movimientos en el índice. */
    public function tieneMovimientos(): bool
    {
        return ($this->entradas_cantidad ?? 0) != 0 || ($this->entradas_importe ?? 0) != 0
            || ($this->salidas_cantidad ?? 0) != 0 || ($this->salidas_importe ?? 0) != 0;
    }
}
