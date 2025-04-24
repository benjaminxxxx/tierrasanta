<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampoCampania extends Model
{
    use HasFactory;
    protected $table = 'campos_campanias';
    protected $fillable = [
        'nombre_campania',
        'campo',
        'gasto_fdm',
        'gasto_agua',
        'gasto_planilla',
        'gasto_cuadrilla',
        'fecha_inicio',
        'fecha_fin',
        'usuario_modificador',

        'gasto_planilla_file',
        'gasto_cuadrilla_file',
        'gasto_resumen_bdd_file',

        'costo_fertilizantes',
        'costo_pesticidas',
        'costo_combustibles',
        'costo_fertilizantes_file',
        'costo_pesticidas_file',
        'costo_combustibles_file',

        'variedad_tuna',
        'sistema_cultivo',
        'tipo_cambio',

        'pencas_x_hectarea',
        'pp_dia_cero_fecha_evaluacion',
        'pp_dia_cero_numero_pencas_madre',
        'pp_resiembra_fecha_evaluacion',
        'pp_resiembra_numero_pencas_madre',

        'brotexpiso_fecha_evaluacion',
        'brotexpiso_actual_brotes_2piso',
        'brotexpiso_brotes_2piso_n_dias',
        'brotexpiso_actual_brotes_3piso',
        'brotexpiso_brotes_3piso_n_dias',
        'brotexpiso_actual_total_brotes_2y3piso',
        'brotexpiso_total_brotes_2y3piso_n_dias'
    ];
    public function camposCampaniasConsumo()
    {
        return $this->hasMany(CamposCampaniasConsumo::class, 'campos_campanias_id');
    }
    public function reporteCostoPlanilla()
    {
        return $this->hasMany(ReporteCostoPlanilla::class, 'campos_campanias_id');
    }
    public function poblacionPlantas()
    {
        return $this->hasMany(PoblacionPlantas::class, 'campania_id');
    }
    public function evaluacionBrotesXPiso()
    {
        return $this->hasMany(EvaluacionBrotesXPiso::class, 'campania_id');
    }

    //consumos()
    public function resumenConsumoProductos()
    {
        return $this->hasMany(ResumenConsumoProductos::class, 'campos_campanias_id');
    }

    public function campo_model()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }
    public function getFechaVigenciaAttribute()
    {
        $date = Carbon::parse($this->fecha_inicio);
        $date->locale('es');

        return $date->translatedFormat('j \d\e F \d\e\l\ Y');
    }
    //atributos para el detalle final de la campaña
    public function getFechaSiembraAttribute()
    {
        $date = Carbon::parse($this->fecha_inicio);

        return Siembra::where('fecha_siembra', '<=', $date)
            ->where('campo_nombre', $this->campo)
            ->latest('fecha_siembra') // Obtiene la siembra más reciente antes de fecha_inicio
            ->value('fecha_siembra') ?? null; // Devuelve solo la fecha o una cadena vacía si no hay resultados
    }
    public static function masProximaAntesDe($fecha,$campo)
    {
        return self::where('fecha_inicio', '<=', $fecha)
            ->where('campo', $campo)
            ->orderByDesc('fecha_inicio')
            ->first();
    }
}
