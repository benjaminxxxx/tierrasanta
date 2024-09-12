<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidadoRiego extends Model
{
    use HasFactory;
    protected $table = 'consolidado_riegos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'regador_documento',
        'regador_nombre',
        'descuento_horas_almuerzo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas_riego',
        'total_horas_observaciones',
        'total_horas_acumuladas',
        'total_horas_jornal',
        'estado'
    ];
    public function getHorasAcumuladasAttribute()
    {
        $horasAcumuladas = HorasAcumuladas::where('documento', $this->regador_documento)
            ->whereDate('fecha_acumulacion', $this->fecha) // Suponiendo que la fecha de uso es relevante
            ->first();

        // Si no hay horas acumuladas, devolver 00:00
        if (!$horasAcumuladas) {
            return '00:00';
        }
        // Convertir minutos acumulados en horas y minutos
        $horas = floor($horasAcumuladas->minutos_acomulados / 60);
        $minutosRestantes = $horasAcumuladas->minutos_acomulados % 60;
        $resultado = '';

        // Formatear horas
        if ($horas > 0) {
            $resultado .= $horas . ' ' . ($horas == 1 ? 'hora' : 'horas');
        }

        // Formatear minutos
        if ($minutosRestantes > 0) {
            if ($horas > 0) {
                $resultado .= ' y ';
            }
            $resultado .= $minutosRestantes . ' ' . ($minutosRestantes == 1 ? 'minuto' : 'minutos');
        }

        // Si no hay horas ni minutos, devolver 00:00
        if (empty($resultado)) {
            return '00:00';
        }

        return $resultado;
    }
}
