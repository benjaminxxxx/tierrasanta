<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GastoAdicionalPorGrupoCuadrilla extends Model
{
    use HasFactory;

    // Tabla asociada al modelo (opcional si el nombre sigue las convenciones)
    protected $table = 'gasto_adicional_por_grupo_cuadrillas';

    // Campos asignables masivamente
    protected $fillable = [
        'monto',
        'descripcion',
        'cua_asistencia_semanal_grupo_id',
        'anio_contable',
        'mes_contable',
        'fecha_gasto'
    ];
    public function getFechaContableAttribute(){
        return "{$this->mes_contable}-{$this->anio_contable}";
    }
    /**
     * Relación con el modelo CuaAsistenciaSemanalGrupo.
     */
    public function cuaAsistenciaSemanalGrupo()
    {
        return $this->belongsTo(CuaAsistenciaSemanalGrupo::class, 'cua_asistencia_semanal_grupo_id');
    }
}
