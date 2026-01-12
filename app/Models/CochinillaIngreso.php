<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class CochinillaIngreso extends Model
{
    use HasFactory;
    protected $table = 'cochinilla_ingresos';
    #region PROPIEDADES
    protected $fillable = [
        'lote',
        'fecha',
        'campo',
        'area',
        'campo_campania_id',
        'observacion',
        'proveedor_kg_exportado',
        'kg_ha',
        'total_kilos',
        'stock_disponible',
        'diferencia_kilos',
        'porcentaje_diferencia',
        'venteado_kilos_ingresados',
        'venteado_limpia',
        'venteado_basura',
        'venteado_polvillo',
        'venteado_limpia_porcentaje',
        'venteado_basura_porcentaje',
        'venteado_polvillo_porcentaje',
        'venteado_diferencia_kilos',
        'venteado_diferencia_porcentaje',
        'filtrado_kilos_ingresados',
        'filtrado_primera',
        'filtrado_segunda',
        'filtrado_tercera',
        'filtrado_piedra',
        'filtrado_basura',
        'filtrado_primera_porcentaje',
        'filtrado_segunda_porcentaje',
        'filtrado_tercera_porcentaje',
        'filtrado_piedra_porcentaje',
        'filtrado_basura_porcentaje',
        'filtrado_diferencia_kilos',
        'filtrado_diferencia_porcentaje',
    ];
    #endregion

    #region RELACIONES
    public function ventas_cochinillas()
    {
        return $this->hasMany(VentaCochinilla::class, 'cochinilla_ingreso_id');
    }
    public function infestaciones()
    {
        return $this->belongsToMany(CochinillaInfestacion::class, 'cochinilla_ingreso_infestacion')
            ->withPivot('kg_asignados')
            ->withTimestamps();
    }

    public function getCamposInfestadosAttribute()
    {
        // Si ya tiene infestaciones asociadas (caso moderno)
        if ($this->infestaciones && $this->infestaciones->isNotEmpty()) {
           
            $campos = $this->infestaciones
                ->pluck('campo_origen_nombre')
                ->unique()
                ->implode(',');

            return $campos !== '' ? $campos : null;
        }

        // Búsqueda en caliente (caso antiguo sin relaciones)
        $campo = $this->campo;
        $fecha = Carbon::parse($this->fecha);
        
        $infestaciones = CochinillaInfestacion::where('campo_origen_nombre', $campo)
            ->whereDate('fecha', '<=', $fecha)
            ->whereDate('fecha', '>=', $fecha->copy()->subDays(60))
            ->get();

        $campos = $infestaciones
            ->pluck('campo_origen_nombre')
            ->unique()
            ->implode(',');

        return $campos !== '' ? $campos : null;
    }

    public function campoRelacionada()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }
    public function detalles()
    {
        return $this->hasMany(CochinillaIngresoDetalle::class);
    }
    public function venteados()
    {
        return $this->hasMany(CochinillaVenteado::class, 'lote', 'lote');
    }
    public function filtrados()
    {
        return $this->hasMany(CochinillaFiltrado::class, 'cochinilla_ingreso_id');
    }
    public function observacionRelacionada()
    {
        return $this->belongsTo(CochinillaObservacion::class, 'observacion', 'codigo');
    }

    public function campoCampania()
    {
        return $this->belongsTo(CampoCampania::class);
    }
    public function detallesMama()
    {
        return $this->hasMany(CochinillaIngresoDetalle::class)
            ->whereHas('observacionRelacionada', function ($q) {
                $q->where('es_cosecha_mama', true);
            });
    }
    protected $appends = [
        'total_filtrado_kilos',
        'total_filtrado_primera',
        'total_filtrado_segunda',
        'total_filtrado_tercera',
        'total_filtrado_piedra',
        'total_filtrado_basura',
        'total_filtrado_total',
    ];


    public function getTotalFiltradoKilosAttribute()
    {
        return $this->filtrados->sum('kilos_ingresados');
    }

    public function getTotalFiltradoPrimeraAttribute()
    {
        return $this->filtrados->sum('primera');
    }

    public function getTotalFiltradoSegundaAttribute()
    {
        return $this->filtrados->sum('segunda');
    }

    public function getTotalFiltradoTerceraAttribute()
    {
        return $this->filtrados->sum('tercera');
    }

    public function getTotalFiltradoPiedraAttribute()
    {
        return $this->filtrados->sum('piedra');
    }

    public function getTotalFiltradoBasuraAttribute()
    {
        return $this->filtrados->sum('basura');
    }

    public function getTotalFiltradoTotalAttribute()
    {
        return $this->filtrados->sum(fn ($f) =>
            $f->primera + $f->segunda + $f->tercera + $f->piedra + $f->basura
        );
    }
    #endregion

    #region SCOPES
    /**
     * La campaña tambiene tiene su propiedad fecha de siembra
     * aqui agregamos esta funcion tambien porque a veces la campaña inicia antes o despues de la siembra y puede ocacionar errores, entonces 
     * cuando se ingresa la cochinilla en vez de buscar la fecha de siembra de la campaña, solo revisamos la siembra mas proxima antes de esta fecha
     */
    public function getFechaSiembraAttribute()
    {
        $date = Carbon::parse($this->fecha);

        return Siembra::where('fecha_siembra', '<=', $date)
            ->where('campo_nombre', $this->campo)
            ->latest('fecha_siembra') // Obtiene la siembra más reciente antes de fecha_inicio
            ->value('fecha_siembra') ?? null; // Devuelve solo la fecha o una cadena vacía si no hay resultados
    }
    public function getUsoInfestacionesAttribute(): bool
    {
        return $this->infestaciones()->exists();
    }
    #endregion

    #region CALCULO VENTEAO

    public function getFechaProcesoVenteadoAttribute()
    {
        return optional($this->venteados()->latest('fecha_proceso')->first())->fecha_proceso;
    }

    public function getTotalVenteadoKilosIngresadosAttribute()
    {
        return $this->venteados->sum('kilos_ingresado');
    }
    public function getTotalVenteadoKilosIngresadosPorcentajeAttribute()
    {
        return $this->total_kilos > 0 ? ($this->total_venteado_kilos_ingresados / $this->total_kilos) * 100 : 0;
    }

    public function getTotalVenteadoLimpiaAttribute()
    {
        return $this->venteados->sum('limpia');
    }
    public function getTotalVenteadoBasuraAttribute()
    {
        return $this->venteados->sum('basura');
    }
    public function getTotalVenteadoPolvilloAttribute()
    {
        return $this->venteados->sum('polvillo');
    }
    public function getTotalVenteadoTotalAttribute()
    {
        return $this->total_venteado_limpia + $this->total_venteado_basura + $this->total_venteado_polvillo;
    }
    public function getPorcentajeVenteadoLimpiaAttribute()
    {
        return $this->total_venteado_total > 0 ? ($this->total_venteado_limpia / $this->total_venteado_total) * 100 : 0;
    }

    public function getPorcentajeVenteadoBasuraAttribute()
    {
        return $this->total_venteado_total > 0 ? ($this->total_venteado_basura / $this->total_venteado_total) * 100 : 0;
    }

    public function getPorcentajeVenteadoPolvilloAttribute()
    {
        return $this->total_venteado_total > 0 ? ($this->total_venteado_polvillo / $this->total_venteado_total) * 100 : 0;
    }
    public function getDiferenciaAttribute()
    {
        return $this->total_kilos - $this->total_venteado_kilos_ingresados;
    }
    public function getPorcentajeDiferenciaAttribute()
    {
        if ($this->total_kilos == 0) {
            return 0; // o null, o cualquier valor que tenga sentido en tu caso
        }

        return ($this->diferencia / $this->total_kilos) * 100;
    }
    #endregion

    #region CALCULO FILTRADO
    public function getFechaProcesoFiltradoAttribute()
    {
        return optional($this->filtrados()->latest('fecha_proceso')->first())->fecha_proceso;
    }

    public function getTotalFiltradoKilosIngresadosAttribute()
    {
        return optional($this->filtrados)->sum('kilos_ingresados') ?? 0;
    }

    public function getTotalFiltradoKilosIngresadosPorcentajeAttribute()
    {
        return $this->total_kilos > 0 ? ($this->total_filtrado_kilos_ingresados / $this->total_kilos) * 100 : 0;
    }

    public function getFiltrado123Attribute()
    {
        return $this->total_filtrado_primera
            + $this->total_filtrado_segunda
            + $this->total_filtrado_tercera;
    }
    public function getFiltrado123XHaAttribute(): float
    {
        return $this->area ? round($this->filtrado123 / $this->area, 2) : 0.0;
    }

    private function calcularPorcentaje($totalCategoria)
    {
        return $this->total_filtrado_total > 0 ? ($totalCategoria / $this->total_filtrado_total) * 100 : 0;
    }

    public function getPorcentajeFiltradoPrimeraAttribute()
    {
        return $this->calcularPorcentaje($this->total_filtrado_primera);
    }

    public function getPorcentajeFiltradoSegundaAttribute()
    {
        return $this->calcularPorcentaje($this->total_filtrado_segunda);
    }

    public function getPorcentajeFiltradoTerceraAttribute()
    {
        return $this->calcularPorcentaje($this->total_filtrado_tercera);
    }

    public function getPorcentajeFiltradoPiedraAttribute()
    {
        return $this->calcularPorcentaje($this->total_filtrado_piedra);
    }

    public function getPorcentajeFiltradoBasuraAttribute()
    {
        return $this->calcularPorcentaje($this->total_filtrado_basura);
    }
    public function getDiferenciaFiltradoAttribute()
    {
        return $this->total_kilos - $this->total_filtrado_kilos_ingresados;
    }
    public function getPorcentajeDiferenciaFiltradoAttribute()
    {
        if ($this->total_kilos == 0) {
            return 0; // o null, o cualquier valor que tenga sentido en tu caso
        }

        return ($this->diferencia_filtrado / $this->total_kilos) * 100;
    }


    #endregion
    #region CALCULOS GENERALES
    //Material Util de venteado
    public function getMaterialUtilVenteadoAttribute()
    {
        return $this->total_venteado_limpia + $this->total_venteado_polvillo;
    }
    public function getMaterialUtilVenteadoPorcentajeAttribute()
    {
        return $this->total_venteado_kilos_ingresados > 0 ? ($this->material_util_venteado / $this->total_venteado_kilos_ingresados) * 100 : 0;
    }
    //Material Util de filtrado
    public function getMaterialUtilFiltradoAttribute()
    {
        return $this->total_filtrado_primera + $this->total_filtrado_segunda + $this->total_filtrado_tercera;
    }
    public function getMaterialUtilFiltradoPorcentajeAttribute()
    {
        return $this->total_filtrado_kilos_ingresados > 0 ? ($this->material_util_filtrado / $this->total_filtrado_kilos_ingresados) * 100 : 0;
    }
    //Merma de ingreso a venteado
    public function getMermaIngresoVenteadoAttribute()
    {
        return $this->total_kilos - $this->total_venteado_kilos_ingresados;
    }
    public function getMermaIngresoVenteadoPorcentajeAttribute()
    {
        return $this->total_kilos > 0 ? ($this->merma_ingreso_venteado / $this->total_kilos) * 100 : 0;
    }
    //Merma de venteado a filtrado
    public function getMermaVenteadoFiltradoAttribute()
    {
        return $this->material_util_venteado - $this->total_filtrado_kilos_ingresados;
    }
    public function getMermaVenteadoFiltradoPorcentajeAttribute()
    {
        return $this->material_util_venteado > 0 ? ($this->merma_venteado_filtrado / $this->material_util_venteado) * 100 : 0;
    }
    //Merma de ingreso a filtrado
    public function getMermaIngresoFiltradoAttribute()
    {
        return $this->total_kilos - $this->total_filtrado_kilos_ingresados;
    }
    public function getMermaIngresoFiltradoPorcentajeAttribute()
    {
        return $this->total_kilos > 0 ? ($this->merma_ingreso_filtrado / $this->total_kilos) * 100 : 0;
    }
    #endregion
    #region Ventas
    public function getCantidadVendidaAttribute()
    {
        return $this->ventas_cochinillas->sum('cantidad_seca');
    }
    #endregion

}
