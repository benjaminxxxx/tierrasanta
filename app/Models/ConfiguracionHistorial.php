<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Valor de un parámetro desde una fecha (día 1 del mes). fecha_fin la mantiene PlanillaParametroProceso. No escribir
 * directo: el proceso valida los candados (valores ya usados por una planilla) y guarda motivo y auditoría.
 */
class ConfiguracionHistorial extends Model
{
    use HasFactory;

    protected $table = 'configuracion_historial';

    protected $fillable = [
        'configuracion_codigo',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'motivo',
        'registrado_por',
    ];

    protected $casts = [
        'valor' => 'decimal:4',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'activo' => 'boolean',
    ];

    public function configuracion()
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_codigo', 'codigo');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
