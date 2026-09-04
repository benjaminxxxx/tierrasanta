<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CalculoHelper
{
    public static function faltasInjustificadas($totalHoras)
    {
        return $totalHoras == 0 ? 1 : 0;
    }
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
     * Reparte el tiempo total trabajado entre los registros que se solapan,
     * de forma que cada minuto de presencia real se divida entre la cantidad
     * de campos atendidos SIMULTÁNEAMENTE en ese instante.
     *
     * A diferencia de calcularMinutosJornalParcial() (que solo obtiene el total
     * de presencia fusionando solapamientos), esta función devuelve CUÁNTO
     * de ese total le corresponde a CADA registro individual.
     *
     * Ejemplo: Si riega 2 campos a la vez de 07:00 a 08:30 (1.5h), cada campo
     * recibe 0.75h. Si luego pasa a regar 3 campos a la vez, cada uno recibe
     * un tercio del tiempo de ese tramo. Un registro que dura 3 horas pero
     * cuya concurrencia cambia a la mitad de su duración, recibe la suma de
     * sus porciones en cada sub-tramo (no un valor único).
     *
     * @param array $intervalos ['clave' => ['hora_inicio' => 'HH:mm', 'hora_fin' => 'HH:mm']]
     * @return array ['clave' => minutosPonderados] — la suma de todos los valores
     *               es exactamente igual al total de calcularMinutosJornalParcial()
     *               sobre los mismos intervalos (no hay doble conteo ni pérdida).
     */
    public static function calcularHorasPonderadasPorConcurrenciaSinAlmuerzo(array $intervalos): array
    {

        if (empty($intervalos)) {
            return [];
        }

        $items = [];
        $puntos = [];

        foreach ($intervalos as $clave => $intervalo) {
            $inicio = self::horaAMinutos($intervalo['hora_inicio']);
            $fin = self::horaAMinutos($intervalo['hora_fin']);

            $items[$clave] = ['inicio' => $inicio, 'fin' => $fin];
            $puntos[$inicio] = true;
            $puntos[$fin] = true;
        }

        $limites = array_keys($puntos);
        sort($limites);

        $resultado = array_fill_keys(array_keys($items), 0.0);

        // Recorrer cada micro-tramo entre dos límites consecutivos
        for ($i = 0; $i < count($limites) - 1; $i++) {
            $t1 = $limites[$i];
            $t2 = $limites[$i + 1];
            $duracion = $t2 - $t1;

            if ($duracion <= 0) {
                continue;
            }

            // ¿Qué registros están activos durante TODO este micro-tramo?
            $activos = [];
            foreach ($items as $clave => $item) {
                if ($item['inicio'] <= $t1 && $item['fin'] >= $t2) {
                    $activos[] = $clave;
                }
            }

            $n = count($activos);
            if ($n === 0) {
                continue;
            }

            $porcion = $duracion / $n;

            foreach ($activos as $clave) {
                $resultado[$clave] += $porcion;
            }
        }

        return $resultado; // minutos por clave
    }
    /**
     * Reparte el tiempo total trabajado entre los registros que se solapan
     * y compensa los centavos de redondeo (2 decimales) dentro de cada micro-tramo.
     *
     * @param array $intervalos ['id' => ['hora_inicio' => 'HH:mm', 'hora_fin' => 'HH:mm']]
     * @param string|null $horaInicioAlmuerzo 'HH:mm'
     * @param string|null $horaFinAlmuerzo 'HH:mm'
     * @return array ['id' => horasPonderadasAjustadasA2Decimales]
     */
    public static function calcularHorasPonderadasPorConcurrencia(
        array $intervalos,
        ?string $horaInicioAlmuerzo = null,
        ?string $horaFinAlmuerzo = null
    ): array {
        if (empty($intervalos)) {
            return [];
        }

        $items = [];
        $puntos = [];

        $almuerzoInicio = $horaInicioAlmuerzo ? self::horaAMinutos($horaInicioAlmuerzo) : null;
        $almuerzoFin = $horaFinAlmuerzo ? self::horaAMinutos($horaFinAlmuerzo) : null;
        $tieneAlmuerzo = (!is_null($almuerzoInicio) && !is_null($almuerzoFin) && $almuerzoFin > $almuerzoInicio);

        foreach ($intervalos as $clave => $intervalo) {
            $inicio = self::horaAMinutos($intervalo['hora_inicio']);
            $fin = self::horaAMinutos($intervalo['hora_fin']);

            $items[$clave] = ['inicio' => $inicio, 'fin' => $fin];
            $puntos[$inicio] = true;
            $puntos[$fin] = true;
        }

        if ($tieneAlmuerzo) {
            $puntos[$almuerzoInicio] = true;
            $puntos[$almuerzoFin] = true;
        }

        $limites = array_keys($puntos);
        sort($limites);

        $resultadoFinal = array_fill_keys(array_keys($items), 0.0);

        // Recorrer cada micro-tramo
        for ($i = 0; $i < count($limites) - 1; $i++) {
            $t1 = $limites[$i];
            $t2 = $limites[$i + 1];
            $duracionMinutos = $t2 - $t1;

            if ($duracionMinutos <= 0) {
                continue;
            }

            // Ignorar tramo de almuerzo
            if ($tieneAlmuerzo && $t1 >= $almuerzoInicio && $t2 <= $almuerzoFin) {
                continue;
            }

            // Identificar registros activos en este tramo
            $activos = [];
            foreach ($items as $clave => $item) {
                if ($item['inicio'] <= $t1 && $item['fin'] >= $t2) {
                    $activos[] = $clave;
                }
            }

            $n = count($activos);
            if ($n === 0) {
                continue;
            }

            // Horas reales exactas a repartir en este grupo (ej. 1.0 hora, 1.5 horas, etc.)
            $horasGrupoReales = $duracionMinutos / 60;

            // Reparto teórico flotante exacto
            $cuotaExacta = $horasGrupoReales / $n;

            // --- COMPENSACIÓN DE REDONDEO A 2 DECIMALES EN EL GRUPO ---
            $asignacionPiso = [];
            $residuos = [];
            $sumaPiso = 0.0;

            foreach ($activos as $clave) {
                // Truncamos a 2 decimales (suelo)
                $piso = floor($cuotaExacta * 100) / 100;
                $asignacionPiso[$clave] = $piso;
                $sumaPiso += $piso;

                // Guardamos el residuo o fracción no asignada
                $residuos[$clave] = $cuotaExacta - $piso;
            }

            // Diferencia en centavos de hora que falta para completar el total del grupo
            // Se escala a enteros (ej. 1 centavo, 2 centavos) para evitar imprecisiones flotantes
            $centavosFaltantes = (int) round(($horasGrupoReales - $sumaPiso) * 100);

            // Ordenamos las claves del grupo por la mayor fracción de residuo
            arsort($residuos);

            // Distribuimos los centavos sobrantes uno a uno a los registros con mayor residuo
            foreach ($residuos as $clave => $residuo) {
                if ($centavosFaltantes <= 0) {
                    break;
                }
                $asignacionPiso[$clave] = round($asignacionPiso[$clave] + 0.01, 2);
                $centavosFaltantes--;
            }

            // Acumular el resultado asignado del grupo al total de cada registro
            foreach ($activos as $clave) {
                $resultadoFinal[$clave] += $asignacionPiso[$clave];
            }
        }

        // Retorna las horas directamente redondeadas y cuadradas a 2 decimales por registro
        return array_map(fn($v) => round($v, 2), $resultadoFinal);
    }
    /**
     * Convierte un total de minutos transcurridos en el día al formato de hora 'HH:mm'.
     *
     * @param int $minutos
     * @return string
     */
    public static function minutosAHora(int $minutos): string
    {
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;

        return sprintf('%02d:%02d', $horas, $mins);
    }
    /**
     * Reparte el tiempo total trabajado entre los registros que se solapan
     * y genera una explicación detallada por micro-tramo.
     *
     * @return array ['totales' => ['id' => float], 'explicacion' => array]
     */
    public static function calcularHorasPonderadasConExplicacion(
        array $intervalos,
        ?string $horaInicioAlmuerzo = null,
        ?string $horaFinAlmuerzo = null
    ): array {
        if (empty($intervalos)) {
            return ['totales' => [], 'explicacion' => []];
        }

        $items = [];
        $puntos = [];

        $almuerzoInicio = $horaInicioAlmuerzo ? self::horaAMinutos($horaInicioAlmuerzo) : null;
        $almuerzoFin = $horaFinAlmuerzo ? self::horaAMinutos($horaFinAlmuerzo) : null;
        $tieneAlmuerzo = (!is_null($almuerzoInicio) && !is_null($almuerzoFin) && $almuerzoFin > $almuerzoInicio);

        foreach ($intervalos as $clave => $intervalo) {
            $inicio = self::horaAMinutos($intervalo['hora_inicio']);
            $fin = self::horaAMinutos($intervalo['hora_fin']);

            $items[$clave] = [
                'inicio' => $inicio,
                'fin' => $fin,
                'nombre' => $intervalo['nombre'] ?? "Registro #{$clave}",
            ];
            $puntos[$inicio] = true;
            $puntos[$fin] = true;
        }

        if ($tieneAlmuerzo) {
            $puntos[$almuerzoInicio] = true;
            $puntos[$almuerzoFin] = true;
        }

        $limites = array_keys($puntos);
        sort($limites);

        $resultadoFinal = array_fill_keys(array_keys($items), 0.0);
        $explicacion = [];

        for ($i = 0; $i < count($limites) - 1; $i++) {
            $t1 = $limites[$i];
            $t2 = $limites[$i + 1];
            $duracionMinutos = $t2 - $t1;

            if ($duracionMinutos <= 0) {
                continue;
            }

            $hInicioStr = self::minutosAHora($t1);
            $hFinStr = self::minutosAHora($t2);
            $rangoStr = "{$hInicioStr} - {$hFinStr}";

            // Tramo de almuerzo
            if ($tieneAlmuerzo && $t1 >= $almuerzoInicio && $t2 <= $almuerzoFin) {
                $explicacion[] = [
                    'tramo' => $rangoStr,
                    'duracion' => $duracionMinutos / 60,
                    'es_almuerzo' => true,
                    'descripcion' => 'Horario de Almuerzo (0 hrs computables)',
                    'reparticion' => [],
                ];
                continue;
            }

            $activos = [];
            foreach ($items as $clave => $item) {
                if ($item['inicio'] <= $t1 && $item['fin'] >= $t2) {
                    $activos[] = $clave;
                }
            }

            $n = count($activos);
            if ($n === 0) {
                continue;
            }

            $horasGrupoReales = $duracionMinutos / 60;
            $cuotaExacta = $horasGrupoReales / $n;

            $asignacionPiso = [];
            $residuos = [];
            $sumaPiso = 0.0;

            foreach ($activos as $clave) {
                $piso = floor($cuotaExacta * 100) / 100;
                $asignacionPiso[$clave] = $piso;
                $sumaPiso += $piso;
                $residuos[$clave] = $cuotaExacta - $piso;
            }

            $centavosFaltantes = (int) round(($horasGrupoReales - $sumaPiso) * 100);
            arsort($residuos);

            foreach ($residuos as $clave => $residuo) {
                if ($centavosFaltantes <= 0) {
                    break;
                }
                $asignacionPiso[$clave] = round($asignacionPiso[$clave] + 0.01, 2);
                $centavosFaltantes--;
            }

            $reparticionDetalle = [];
            foreach ($activos as $clave) {
                $resultadoFinal[$clave] += $asignacionPiso[$clave];
                $reparticionDetalle[] = [
                    'campo' => $items[$clave]['nombre'],
                    'horas' => $asignacionPiso[$clave],
                ];
            }

            $nombresCampos = array_map(fn($k) => $items[$k]['nombre'], $activos);

            $explicacion[] = [
                'tramo' => $rangoStr,
                'duracion' => $horasGrupoReales,
                'es_almuerzo' => false,
                'campos_count' => $n,
                'descripcion' => "{$horasGrupoReales}h compartidas entre {$n} campo(s): " . implode(', ', $nombresCampos),
                'reparticion' => $reparticionDetalle,
            ];
        }

        return [
            'totales' => array_map(fn($v) => round($v, 2), $resultadoFinal),
            'explicacion' => $explicacion,
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
