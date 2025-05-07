<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class CochinillaIngreso extends Model
{
    use HasFactory;
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
        return $this->hasMany(CochinillaFiltrado::class, 'lote', 'lote');
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
        return $this->total_filtrado_primera
            + $this->total_filtrado_segunda
            + $this->total_filtrado_tercera
            + $this->total_filtrado_piedra
            + $this->total_filtrado_basura;
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

}
