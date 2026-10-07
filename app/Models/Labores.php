<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labores extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "labores";
    protected $fillable = [
        'nombre_labor',
        'codigo_mano_obra',
        'tipo_asistencia_codigo', // labor de suspensión: DM, V, FR… (plan_tipo_asistencias.codigo)
        'codigo',
        'vigente_desde', // desde cuándo el código significa esta labor (antes: labor_vigencias)
        'estandar_produccion',
        'unidades',
        'tramos_bonificacion',
        'creado_por',
        'actualizado_por',
        'eliminado_por',
        'se_paga_con_jornal'
    ];
    public function manoObra()
    {
        return $this->belongsTo(ManoObra::class, 'codigo_mano_obra', 'codigo');
    }
    public function tipoAsistencia()
    {
        return $this->belongsTo(PlanTipoAsistencia::class, 'tipo_asistencia_codigo', 'codigo');
    }
    /** Lo que significó este código antes de reasignarse, de lo más reciente a lo más antiguo. */
    public function vigenciasAnteriores()
    {
        return $this->hasMany(LaborVigencia::class, 'codigo', 'codigo')->orderByDesc('hasta');
    }

    public function getEsSuspensionAttribute(): bool
    {
        return filled($this->tipo_asistencia_codigo);
    }
    protected $casts = [
        'vigente_desde' => 'date',
        'tramos_bonificacion' => 'array',
        'se_paga_con_jornal' => 'boolean'
    ];
}
