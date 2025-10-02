<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadResumenPorTramo extends Model
{
    use HasFactory;

    protected $table = 'cuad_resumen_por_tramos';

    protected $fillable = [
        'grupo_codigo',
        'color',
        'modalidad_pago',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'descripcion',
        'condicion',
        'fecha',
        'fecha_acumulada',
        'recibo',
        'orden',
        'deuda_actual',
        'deuda_acumulada',
        'tramo_id',
        'tramo_acumulado_id',
        'excel_reporte_file',
    ];
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'grupo_codigo');
    }
    public function tramo()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_id');
    }
    public function grupoLaboral()
    {
        return $this->hasOne(
            CuadTramoLaboralGrupo::class,
            'codigo_grupo',
            'grupo_codigo'
        )->where('cuad_tramo_laboral_id', $this->tramo_id);
    }
    public function cuadrilleros()
    {
        if ($this->grupoLaboral) {
            return $this->grupoLaboral
                ->cuadrilleros() // relación CuadTramoLaboralGrupo → CuadTramoLaboralCuadrillero
                ->with('cuadrillero') // eager load del modelo Cuadrillero
                ->get()
                ->map(fn($rel) => $rel->cuadrillero);
        }

        return collect();
    }

    public function tramoAcumulado()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_acumulado_id');
    }
}
