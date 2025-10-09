<?php

namespace App\Services\Handsontable;
use App\Models\CuadRegistroDiario;
use App\Models\CuadTramoLaboral as TramoLaboral;
use App\Models\CuadTramoLaboralGrupo as GrupoTramo;
use App\Models\CuadTramoLaboralCuadrillero as CuadrilleroTramo;
use App\Models\CuaGrupo as Grupo;
use App\Services\Cuadrilla\TramoLaboralServicio;
use Illuminate\Support\Collection;
use Carbon\Carbon;
class HSTCuadrillaReporteSemanalHoras
{
    private TramoLaboral $tramoLaboral;
    private Carbon $fechaInicio;
    private Carbon $fechaFin;
    private Collection $diasDelTramo;
    private Collection $registrosDiarios;

    /**
     * @param TramoLaboral $tramoLaboral El modelo del cual se generarán los datos.
     */
    public function __construct(TramoLaboral $tramoLaboral)
    {
        $this->tramoLaboral = $tramoLaboral;
        $this->initializeDateRange();
        $this->fetchDailyRecords();
    }

    /**
     * Orquesta la generación de datos y devuelve el array final.
     *
     * @return array
     */
    public function generate(): array
    {
        $handsontableData = [];
        $gruposEnTramos = $this->obtenerListaOficial();

        foreach ($gruposEnTramos as $grupoEnTramo) {
            
            // Añadir la fila de cabecera para el grupo
            $handsontableData[] = $this->buildGroupHeaderRow($grupoEnTramo->grupo);

            // Añadir las filas para cada cuadrillero del grupo
            $orden = 0;
            foreach ($grupoEnTramo->cuadrilleros as $cuadrilleroEnTramo) {
                $orden++;
                $handsontableData[] = $this->buildCrewMemberRow($cuadrilleroEnTramo, $grupoEnTramo->grupo, $orden);
            }
        }

        if (count($handsontableData) > 0) {
            $handsontableData[] = $this->buildTotalsRow($handsontableData);
        }

        return $handsontableData;
    }

    /**
     * Genera una lista simplificada de los grupos para otros usos (e.g., leyendas).
     *
     * @return array
     */
    public function getGroupList(): array
    {
        return $this->obtenerListaOficial()->map(function (GrupoTramo $grupoTramo) {
            return [
                'codigo' => $grupoTramo->grupo->codigo,
                'color'  => $grupoTramo->grupo->color,
                'nombre' => $grupoTramo->grupo->nombre,
                'orden'  => $grupoTramo->orden,
            ];
        })->toArray();
    }

    // --- MÉTODOS PRIVADOS (DELEGACIÓN DE RESPONSABILIDADES) ---

    /**
     * Inicializa las fechas de inicio/fin y la colección de días del tramo.
     */
    private function initializeDateRange(): void
    {
        $this->fechaInicio = Carbon::parse($this->tramoLaboral->fecha_inicio)->startOfDay();
        $this->fechaFin = Carbon::parse($this->tramoLaboral->fecha_fin)->endOfDay();

        $this->diasDelTramo = collect();
        for ($d = $this->fechaInicio->copy(); $d->lte($this->fechaFin); $d->addDay()) {
            $this->diasDelTramo->push($d->copy());
        }
    }

    /**
     * Obtiene y agrupa todos los registros diarios necesarios para el tramo.
     */
    private function fetchDailyRecords(): void
    {
        $this->registrosDiarios = CuadRegistroDiario::whereBetween('fecha', [$this->fechaInicio, $this->fechaFin])
            ->get()
            ->groupBy(fn($item) => $item->cuadrillero_id . '|' . $item->codigo_grupo);
    }
    
    /**
     * Obtiene los grupos y sus respectivos cuadrilleros ordenados.
     */
    private function obtenerListaOficial(): Collection
    {
        return TramoLaboralServicio::obtenerListaOficial($this->tramoLaboral->id);
    }

    /**
     * Construye la fila de cabecera para un grupo.
     */
    private function buildGroupHeaderRow(Grupo $grupo): array
    {
        return [
            'orden'   => null,
            'header'  => true,
            'nombres' => $grupo->nombre,
            'color'   => $grupo->color,
        ];
    }

    /**
     * Construye la fila completa de datos para un miembro de la cuadrilla.
     */
    private function buildCrewMemberRow(CuadrilleroTramo $cuadrilleroEnTramo, Grupo $grupo, int $orden): array
    {
        $fila = [
            'orden'          => $orden,
            'cuadrillero_id' => $cuadrilleroEnTramo->cuadrillero_id,
            'codigo_grupo'   => $grupo->codigo,
            'header'         => false,
            'nombres'        => $cuadrilleroEnTramo->nombres,
            'color'          => $grupo->color,
        ];

        // Lógica para poblar los datos diarios
        $clave = $cuadrilleroEnTramo->cuadrillero_id . '|' . $grupo->codigo;
        $registrosDelCuadrillero = $this->registrosDiarios->get($clave, collect());
        
        $totals = $this->populateDailyDataAndGetTotals($fila, $registrosDelCuadrillero);

        // Añadir totales a la fila
        $fila['total_bono'] = $totals['bono'];
        $fila['total_costo'] = $totals['costo'] + $totals['bono'];

        return $fila;
    }
    /**
     * Calcula y construye la fila de totales a partir de los datos ya generados.
     *
     * @param array $dataRows Todas las filas generadas (cabeceras y datos de cuadrilleros).
     * @return array La fila de totales formateada.
     */
    private function buildTotalsRow(array $dataRows): array
    {
        // 1. Inicializar la fila de totales con valores en cero
        $totals = [
            'nombres'  => 'TOTALES',
            'is_total' => true,      // Una bandera para identificarla en el frontend si es necesario
            'color'    => '#80b5eaff', // Un color oscuro y neutro para la fila de totales
        ];
        
        // Pre-llenar todas las columnas numéricas para evitar errores
        foreach ($this->diasDelTramo as $index => $dia) {
            $keyIndex = $index + 1;
            $totals["dia_{$keyIndex}"]    = 0;
            $totals["jornal_{$keyIndex}"] = 0.0;
            $totals["bono_{$keyIndex}"]   = 0.0;
        }
        $totals['total_costo'] = 0.0;
        $totals['total_bono']  = 0.0;


        // 2. Filtrar solo las filas de datos de cuadrilleros (ignorando las cabeceras de grupo)
        $workerRows = array_filter($dataRows, fn($row) => isset($row['cuadrillero_id']));
        
        // 3. Iterar sobre las filas de datos para calcular los totales
        foreach ($workerRows as $row) {
            // Sumar totales generales
            $totals['total_costo'] += (float) $row['total_costo'];
            $totals['total_bono']  += (float) $row['total_bono'];

            // Iterar sobre cada día del tramo para sumar las columnas dinámicas
            foreach ($this->diasDelTramo as $index => $dia) {
                $keyIndex = $index + 1;
                
                // REQUERIMIENTO ESPECIAL: Para las horas, contar trabajadores, no sumar horas.
                // Si la columna 'dia_X' tiene un valor numérico (no es '-'), se incrementa el contador.
                if (is_numeric($row["dia_{$keyIndex}"])) {
                    $totals["dia_{$keyIndex}"]++;
                }
                
                // Sumar los jornales y bonos diarios
                if (is_numeric($row["jornal_{$keyIndex}"])) {
                    $totals["jornal_{$keyIndex}"] += (float) $row["jornal_{$keyIndex}"];
                }
                if (is_numeric($row["bono_{$keyIndex}"])) {
                    $totals["bono_{$keyIndex}"] += (float) $row["bono_{$keyIndex}"];
                }
            }
        }
        
        // Formatear los totales generales
        $totals['total_costo'] = formatear_numero($totals['total_costo']);
        $totals['total_bono']  = formatear_numero($totals['total_bono']);

        // Formatear los totales de cada día
        foreach ($this->diasDelTramo as $index => $dia) {
            $keyIndex = $index + 1;
            $totals["jornal_{$keyIndex}"] = formatear_numero($totals["jornal_{$keyIndex}"]);
            $totals["bono_{$keyIndex}"]   = formatear_numero($totals["bono_{$keyIndex}"]);
            // La columna 'dia_X' es un conteo, por lo que no se formatea como número decimal.
        }

        // 5. Devolver la fila de totales ya formateada
        
        return $totals;
    }
    /**
     * Itera sobre los días del tramo, puebla los datos en la fila y calcula los totales.
     * @param array &$fila La fila del cuadrillero (pasada por referencia para modificarla).
     * @param Collection $registros Los registros diarios de ese cuadrillero.
     * @return array Un array asociativo con los totales de 'costo' y 'bono'.
     */
    private function populateDailyDataAndGetTotals(array &$fila, Collection $registros): array
    {
        $totalCosto = 0;
        $totalBono = 0;

        foreach ($this->diasDelTramo as $index => $dia) {
            $fechaStr = $dia->toDateString();
            
            // Busca el registro para el día actual
            $registro = $registros->first(
                fn($item) => Carbon::parse($item->fecha)->toDateString() === $fechaStr
            );
            
            $horas  = $registro && $registro->total_horas > 0 ? $registro->total_horas : '-';
            $jornal = $registro && $registro->costo_dia > 0   ? $registro->costo_dia   : '-';
            $bono   = $registro && $registro->total_bono > 0  ? $registro->total_bono  : '-';

            // Asigna los valores a la fila
            $keyIndex = $index + 1;
            $fila["dia_{$keyIndex}"]    = $horas;
            $fila["jornal_{$keyIndex}"] = $jornal;
            $fila["bono_{$keyIndex}"]   = $bono;
            
            // Acumula los totales
            $totalCosto += is_numeric($jornal) ? (float) $jornal : 0;
            $totalBono  += is_numeric($bono) ? (float) $bono : 0;
        }
        
        return ['costo' => $totalCosto, 'bono' => $totalBono];
    }
}