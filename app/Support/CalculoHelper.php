<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CalculoHelper
{
    /**
     * Calcula el monto real que el trabajador debe recibir basado en su 
     * sueldo pactado y las horas efectivamente trabajadas.
     *
     * @param float $sueldoManoMes  Sueldo total pactado (ej: 2200)
     * @param float $horasTrabajadas Horas que asistió el trabajador (ej: 140)
     * @param float $totalHorasMes   Horas base del mes (ej: 160)
     * @return float
     */
    public static function calcularSueldoManoProporcional($sueldoManoMes, $horasTrabajadas, $totalHorasMes)
    {
        if ($totalHorasMes <= 0)
            return 0;

        // Calculamos cuánto vale su hora "en la mano"
        $valorHoraMano = $sueldoManoMes / $totalHorasMes;

        // Retornamos el pago proporcional a sus horas reales
        return round($valorHoraMano * $horasTrabajadas, 2);
    }
    /**
     * Calcula el costo en blanco y negro de una labor específica.
     *
     * @param float $horasLabor         Horas dedicadas a la labor específica (ej: 30.5)
     * @param float $totalHorasMes      Total de horas trabajadas en el mes (ej: 160)
     * @param float $netoRecibidoReal   Lo que el trabajador cobró en total (ej: 2200)
     * @param float $costoTotalEmpresa  Suma de (Sueldo Bruto + Aportes Empleador) del PLAME
     * @param float $netoBoletaPLAME    El "Neto a Pagar" que figura en la boleta legal
     * @return array
     */
    /*
    public static function calcularCostoLabor($horasLabor, $totalHorasMes, $netoRecibidoReal, $costoTotalEmpresa, $netoBoletaPLAME)
    {
        if ($totalHorasMes <= 0) {
            return ['blanco' => 0, 'negro' => 0, 'total' => 0];
        }

        // 1. Calculamos el "Negro" total del mes (la diferencia de lo pactado vs lo legal)
        $totalNegroMes = $netoRecibidoReal - $netoBoletaPLAME;
        if ($totalNegroMes < 0)
            $totalNegroMes = 0;

        // 2. Hallamos el factor de proporción de la labor respecto al tiempo total
        $proporcion = $horasLabor / $totalHorasMes;

        // 3. Distribuimos el Costo Blanco (Costo Empresa) y el Negro según la proporción
        $costoBlancoLabor = $costoTotalEmpresa * $proporcion;
        $costoNegroLabor = $totalNegroMes * $proporcion;

        return [
            'blanco' => round($costoBlancoLabor, 4),
            'negro' => round($costoNegroLabor, 4),
            'total' => round($costoBlancoLabor + $costoNegroLabor, 4),
            'factor_proporcion' => $proporcion
        ];
    }*/
    /**
     * Calcula el costo en blanco y negro proporcional a una labor.
     *
     * @param float $horasLabor           Horas dedicadas a la labor.
     * @param float $totalHorasMes        Total de horas del mes.
     * @param float $costoBlancoTotalMes  Costo blanco mensual completo.
     * @param float $costoNegroTotalMes   Costo negro mensual completo.
     * @return array
     */
    public static function calcularCostoLaborMinimal(
        float $horasLabor,
        float $totalHorasMes,
        float $costoBlancoTotalMes,
        float $costoNegroTotalMes
    ) {
        if ($totalHorasMes <= 0) {
            return ['blanco' => 0, 'negro' => 0, 'total' => 0];
        }

        // Factor proporcional de tiempo
        $factor = $horasLabor / $totalHorasMes;

        // Distribución proporcional
        $blanco = $costoBlancoTotalMes * $factor;
        $negro = $costoNegroTotalMes * $factor;

        return [
            'blanco' => round($blanco, 4),
            'negro' => round($negro, 4),
            'total' => round($blanco + $negro, 4),
            'factor' => $factor
        ];
    }
    /**
     * Calcula el tiempo total de jornal real eliminando solapamientos.
     * * Casos de uso resueltos:
     * 1. Riegos Simultáneos: Si riega 4 campos de 07:00 a 09:00, cuenta solo 120 min de jornal.
     * 2. Solapamientos Parciales: De 07:00-09:00 y 08:00-10:00, cuenta 07:00-10:00 (180 min).
     * 3. Intervalos Separados: De 07:00-12:00 y 13:00-16:00, suma ambos (300 + 180 = 480 min).
     * 4. Riegos Contenidos: Un riego de 07:00-15:00 absorbe cualquier riego corto intermedio.
     *
     * @param array $intervalos [['hora_inicio' => 'HH:mm', 'hora_fin' => 'HH:mm'], ...]
     * @return int Total de minutos de presencia real (jornal)
     */
    public static function calcularMinutosJornalParcial(array $intervalos): int
    {
        if (empty($intervalos)) {
            return 0;
        }

        // 1. Convertir a minutos desde el inicio del día (00:00 = 0)
        $puntos = [];
        foreach ($intervalos as $i) {
            $puntos[] = [
                'inicio' => self::horaAMinutos($i['hora_inicio']),
                'fin' => self::horaAMinutos($i['hora_fin'])
            ];
        }

        // 2. Ordenar por hora de inicio
        usort($puntos, fn($a, $b) => $a['inicio'] <=> $b['inicio']);

        // 3. Fusión de intervalos (Merge Intervals)
        $fusionados = [];
        if (count($puntos) > 0) {
            $fusionados[] = $puntos[0];
        }

        for ($i = 1; $i < count($puntos); $i++) {
            $ultimo = &$fusionados[count($fusionados) - 1];
            $actual = $puntos[$i];

            if ($actual['inicio'] <= $ultimo['fin']) {
                // Hay solapamiento o continuidad, extender el final si es necesario
                $ultimo['fin'] = max($ultimo['fin'], $actual['fin']);
            } else {
                // No hay solapamiento, añadir nuevo intervalo
                $fusionados[] = $actual;
            }
        }

        // 4. Sumar duraciones de intervalos fusionados
        $totalMinutos = 0;
        foreach ($fusionados as $f) {
            $totalMinutos += ($f['fin'] - $f['inicio']);
        }

        return $totalMinutos;
    }

    /**
     * Convierte "HH:mm" o "HH.mm" a minutos totales desde las 00:00
     */
    private static function horaAMinutos(string $hora): int
    {
        $hora = str_replace('.', ':', $hora);
        $partes = explode(':', $hora);

        $h = isset($partes[0]) ? (int) $partes[0] : 0;
        $m = isset($partes[1]) ? (int) $partes[1] : 0;

        return ($h * 60) + $m;
    }
    /**
     * Calcula la diferencia en horas decimales entre dos tiempos.
     * Ejemplo: "07:00:00" a "10:30:00" -> 3.5
     */
    public static function obtenerDiferenciaHoras(string $horaInicio, string $horaFin): float
    {
        if (!$horaInicio || !$horaFin) {
            return 0;
        }

        $inicio = Carbon::parse($horaInicio);
        $fin = Carbon::parse($horaFin);

        // Usamos diffInMinutes para obtener precisión decimal (ej. 30 min = 0.5 horas)
        $minutos = $inicio->diffInMinutes($fin);

        return round($minutos / 60, 2);
    }
    /**
     * Calcula la fecha de cierre real de una campaña dentro de un mes específico.
     */
    public static function obtenerFechaFinalActiva(int $anio, int $mes, $fechaInicioCampania, $fechaFinCampania): string
    {
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        $inicioCampania = Carbon::parse($fechaInicioCampania);
        // Si no hay fecha fin, la campaña sigue abierta, usamos una fecha muy lejana
        $finCampania = $fechaFinCampania ? Carbon::parse($fechaFinCampania) : Carbon::now()->addYears(10);

        // El último día activo es el mínimo entre el fin del mes y el fin de la campaña
        $fechaFinalReal = $finMes->min($finCampania);

        // Si por algún motivo la campaña terminó antes de que empezara el mes (no debería pasar por el filtro)
        // o si la fecha final calculada es menor al inicio del mes:
        if ($fechaFinalReal->isBefore($inicioMes)) {
            return $inicioMes->toDateString();
        }

        return $fechaFinalReal->toDateString();
    }
    public static function valorNumerico($valor): float
    {
        // Si es numérico lo devuelve, si es vacío o cualquier otra cosa, devuelve 0
        return is_numeric($valor) ? (float) $valor : 0.0;
    }


    /**
     * Calcula la cantidad de jornales basados en una jornada de 8 horas.
     * Ejemplo: 4 horas -> 0.5 jornales
     */
    public static function calcularJornales2(string $horaInicio, string $horaFin): float
    {
        $horas = self::obtenerDiferenciaHoras($horaInicio, $horaFin);

        return round($horas / 8, 3);
    }
    /**
     * Calcula el costo total que representa una actividad específica
     * realizada por un empleado, en base a las horas trabajadas y los bonos.
     *
     * @param float|int $totalHoras     Total de horas trabajadas en el día
     * @param float|int $totalJornal    Monto total ganado por el empleado ese día
     * @param float|int $horasParcial   Horas trabajadas en la actividad/campo
     * @param float|int $bonoParcial    Bono asociado a la actividad/campo
     * @return float Costo total (costo proporcional + bono)
     * @throws InvalidArgumentException Si algún parámetro no es válido
     */
    public static function calcularCostoActividad(
        float|int $totalHoras,
        float|int $totalJornal,
        float|int $horasParcial,
        float|int $bonoParcial = 0
    ): float {
        if ($totalHoras <= 0) {
            throw new InvalidArgumentException("El total de horas debe ser mayor que cero.");
        }

        if ($totalJornal < 0 || $horasParcial < 0 || $bonoParcial < 0) {
            throw new InvalidArgumentException("Los valores no pueden ser negativos.");
        }

        // Costo proporcional al tiempo trabajado
        $tasaHora = $totalJornal / $totalHoras;
        $costoParcial = $horasParcial * $tasaHora;

        // Costo total (proporcional + bono)
        return $costoParcial + $bonoParcial;
    }
    /**
     * Calcula la cantidad de jornales en base a las horas trabajadas.
     *
     * @param float|int $totalDeHoras Horas totales trabajadas
     * @return float Cantidad de jornales calculados
     * @throws InvalidArgumentException Si el valor ingresado no es válido
     */
    public static function calcularJornales(float|int $totalDeHoras): float
    {
        if (!is_numeric($totalDeHoras) || $totalDeHoras < 0) {
            throw new InvalidArgumentException("El total de horas debe ser un número positivo.");
        }

        return $totalDeHoras != 0 ? (float) (8 / $totalDeHoras) : 0;
    }
    /**
     * Calcula la duración entre dos fechas y la devuelve en formato legible
     * (ej: "1 año, 2 meses, 3 días").
     *
     * Ambas fechas deben estar en un formato aceptado por Carbon (string o DateTime).
     * Si alguna de las fechas es nula o inválida, retorna null.
     *
     * @param string|null $inicio Fecha de inicio (ej. fecha de infestación)
     * @param string|null $fin    Fecha de fin (ej. fecha de cosecha)
     * @return string|null Duración legible (años, meses y días) o null si no se puede calcular
     */
    public static function calcularDuracionEntreFechas(?string $inicio, ?string $fin): ?string
    {
        if (!$inicio || !$fin) {
            return null;
        }

        $inicio = Carbon::parse($inicio);
        $fin = Carbon::parse($fin);

        $diferencia = $inicio->diff($fin);

        return $diferencia->y . ' año' . ($diferencia->y !== 1 ? 's' : '') . ', '
            . $diferencia->m . ' mes' . ($diferencia->m !== 1 ? 'es' : '') . ', '
            . $diferencia->d . ' día' . ($diferencia->d !== 1 ? 's' : '');
    }

}
