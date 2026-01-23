<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DistribucionCombustible extends Model
{
    use HasFactory;

    protected $table = 'distribucion_combustibles';

    protected $fillable = [
        'fecha',
        'campo',
        'hora_inicio',
        'hora_salida',
        'costo_combustible',
        'actividad',
        'maquinaria_nombre',
        'valor_costo',
        'maquinaria_id',
        'almacen_producto_salida_id',
    ];
    protected $appends = [
        'valor_costo'
    ];
    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class);
    }

    public function salidaCombustible()
    {
        return $this->belongsTo(AlmacenProductoSalida::class, 'almacen_producto_salida_id');
    }
    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (CÁLCULOS DINÁMICOS)
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula horas: (Hora Salida - Hora Inicio)
     */
    public function getHorasAttribute()
    {
        if (!$this->hora_inicio || !$this->hora_salida)
            return 0;

        $inicio = Carbon::parse($this->hora_inicio);
        $fin = Carbon::parse($this->hora_salida);

        // Diferencia en minutos convertida a horas con 2 decimales
        $minutos = $inicio->diffInMinutes($fin);
        return round($minutos / 60, 2);
    }

    /**
     * Calcula Ratio: Mis horas / Suma de horas de todas las distribuciones del mismo parent
     */
    public function getRatioAttribute()
    {
        // Obtenemos todas las distribuciones hermanas (incluida esta)
        $hermanas = self::where('almacen_producto_salida_id', $this->almacen_producto_salida_id)->get();

        $totalHoras = $hermanas->sum(fn($h) => $h->horas);

        if ($totalHoras <= 0)
            return 0;

        return round($this->horas / $totalHoras, 4);
    }

    /**
     * Cantidad Combustible: Cantidad de la Salida * Ratio
     */
    public function getCantidadCombustibleAttribute()
    {
        $salida = $this->salidaCombustible;
        if (!$salida)
            return 0;

        return round($salida->cantidad * $this->ratio, 2);
    }

    /**
     * Valor Costo: Cantidad Calculada * Precio Unitario de la Salida
     */
    public function getValorCostoAttribute()
    {
        $salida = $this->salidaCombustible;
        if (!$salida)
            return 0;

        $precioUnitario = $salida->costo_por_kg ?? 0;
        return round($this->cantidad_combustible * $precioUnitario, 2);
    }
}
