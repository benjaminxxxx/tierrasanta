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
    public function getEsSuspensionAttribute(): bool
    {
        return filled($this->tipo_asistencia_codigo);
    }
    protected $casts = [
        'tramos_bonificacion' => 'array',
        'se_paga_con_jornal' => 'boolean'
    ];
}
