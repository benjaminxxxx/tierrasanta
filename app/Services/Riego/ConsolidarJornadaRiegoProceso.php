<?php

namespace App\Services\Riego;
use App\Models\ConsolidadoRiego as ResumenJornada;
use App\Models\ParametroTemporal;
use App\Models\ReporteDiarioRiego as RegistroDiario;
use App\Models\AcumulacionUso;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use App\Services\Riego\ValidarCruceRiegoServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

// app/Processes/Riego/ConsolidarJornadaRiegoProceso.php
class ConsolidarJornadaRiegoProceso
{
    public function __construct(
        private ValidacionJornadaServicio $validacion,
        private RegistroDiarioServicio $registros,
        private ConsolidadorServicio $consolidador,
        private AcumulacionServicio $acumulacion,
    ) {
    }

    /*
    public function ejecutarGuardadoRegistros(ResumenJornada $resumen, string $fecha, array $data): void
    {
        // Validaciones fuera del transaction
        $mapaCampos = $this->validacion->validarCampos($data);

        // Precálculo: verificar si este resumen ya cedió minutos a otros días
        $minutosYaCedidos = AcumulacionUso::where('consolidado_origen_id', $resumen->id)
            ->sum('minutos_consumidos');

        if ($minutosYaCedidos > 0) {
            // Calcular cuántos minutos acumulados generaría el nuevo set de registros
            $minutosNuevos = $this->calcularMinutosBrutos($data);

            $excedente = max(0, $minutosNuevos - 480);

            if ($excedente < $minutosYaCedidos) {
                // Construir detalle de en qué días se usaron
                $detalle = AcumulacionUso::where('consolidado_origen_id', $resumen->id)
                    ->with('consolidadoDestino')
                    ->get()
                    ->map(
                        fn($uso) =>
                        Carbon::parse($uso->consolidadoDestino->fecha)->format('d/m/Y') .
                        ' (' . intdiv($uso->minutos_consumidos, 60) . 'h ' . ($uso->minutos_consumidos % 60) . 'm)'
                    )
                    ->join(', ');

                throw new Exception(
                    "Este día tiene {$minutosYaCedidos} minutos acumulados que fueron usados en: {$detalle}. " .
                    "Debes desvincular esos usos antes de reducir los registros."
                );
            }
        }

        DB::transaction(function () use ($resumen, $fecha, $data, $mapaCampos) {
            $this->registros->reemplazarRegistros($resumen, $fecha, $data, $mapaCampos);
            $this->consolidador->consolidar($resumen);
        });
    }
    public function ejecutarGuardadoRegistros(array $parametros): void
    {
        $resumen = $parametros['resumen_riego'];
        $fecha = $parametros['fecha'];
        $data = $parametros['data'];
        $horaInicioAlmuerzo = $parametros['hora_inicio_almuerzo'];
        $horaFinAlmuerzo = $parametros['hora_fin_almuerzo'];

        // Validaciones fuera del transaction
        $mapaCampos = $this->validacion->validarCampos($data);

        // Precálculo: verificar si este resumen ya cedió minutos a otros días
        $minutosYaCedidos = AcumulacionUso::where(
            'consolidado_origen_id',
            $resumen->id
        )->sum('minutos_consumidos');

        if ($minutosYaCedidos > 0) {

            // Calcular cuántos minutos acumulados generaría
            // el nuevo set de registros
            $minutosNuevos = $this->calcularMinutosBrutos($data);

            $excedente = max(0, $minutosNuevos - 480);

            if ($excedente < $minutosYaCedidos) {

                $detalle = AcumulacionUso::where(
                    'consolidado_origen_id',
                    $resumen->id
                )
                    ->with('consolidadoDestino')
                    ->get()
                    ->map(
                        fn($uso) =>
                        Carbon::parse($uso->consolidadoDestino->fecha)->format('d/m/Y') .
                        ' (' .
                        intdiv($uso->minutos_consumidos, 60) .
                        'h ' .
                        ($uso->minutos_consumidos % 60) .
                        'm)'
                    )
                    ->join(', ');

                throw new Exception(
                    "Este día tiene {$minutosYaCedidos} minutos acumulados " .
                    "que fueron usados en: {$detalle}. " .
                    "Debes desvincular esos usos antes de reducir los registros."
                );
            }
        }

        DB::transaction(function () use ($resumen, $fecha, $data, $mapaCampos, $horaInicioAlmuerzo, $horaFinAlmuerzo) {

            $this->registros->reemplazarRegistros(
                $resumen,
                $fecha,
                $data,
                $mapaCampos
            );

            $this->consolidador->consolidar(
                $resumen,
                $horaInicioAlmuerzo,
                $horaFinAlmuerzo
            );
        });
    }*/
    public function ejecutarGuardadoRegistros(array $parametros): array
    {
        $resumen = $parametros['resumen_riego'];
        $fecha = $parametros['fecha'];
        $data = $parametros['data'];
        $horaInicioAlmuerzo = $parametros['hora_inicio_almuerzo'];
        $horaFinAlmuerzo = $parametros['hora_fin_almuerzo'];

        // Validaciones fuera de la transacción
        $mapaCampos = $this->validacion->validarCampos($data);

        // Ya no detiene el flujo: solo recolecta los mensajes para mostrarlos junto al éxito
        $conflictos = app(ValidarCruceRiegoServicio::class)->validar($data, $fecha, $resumen->id);

        // Precálculo: verificar si este resumen ya cedió minutos a otros días
        $minutosYaCedidos = AcumulacionUso::where('consolidado_origen_id', $resumen->id)
            ->sum('minutos_consumidos');

        if ($minutosYaCedidos > 0) {
            // 1. Minutos de presencia bruta del nuevo set
            $minutosBrutos = $this->calcularMinutosBrutos($data);

            // 2. Descuento automático de almuerzo si hay horas registradas
            if ($horaInicioAlmuerzo && $horaFinAlmuerzo) {
                $inicioAlm = Carbon::parse($horaInicioAlmuerzo);
                $finAlm = Carbon::parse($horaFinAlmuerzo);

                if ($finAlm > $inicioAlm) {
                    $minutosAlmuerzo = $inicioAlm->diffInMinutes($finAlm);
                    $minutosBrutos = max(0, $minutosBrutos - $minutosAlmuerzo);
                }
            }

            // 3. Límite dinámico según la fecha
            $limiteMinutos = ParametroTemporal::limiteMinutosDiarios($fecha);

            // 4. Calcular el nuevo excedente real generado para la bolsa
            $excedenteNuevos = 0;
            if (!$resumen->no_acumular_horas && $minutosBrutos > $limiteMinutos) {
                $excedenteNuevos = $minutosBrutos - $limiteMinutos;
            }

            // 5. Validar que la nueva bolsa cubra lo que ya se prestó
            if ($excedenteNuevos < $minutosYaCedidos) {
                $detalle = AcumulacionUso::where('consolidado_origen_id', $resumen->id)
                    ->with('consolidadoDestino')
                    ->get()
                    ->map(function ($uso) {
                        $fechaDestino = Carbon::parse($uso->consolidadoDestino->fecha)->format('d/m/Y');
                        $hrs = intdiv($uso->minutos_consumidos, 60);
                        $mins = $uso->minutos_consumidos % 60;
                        return "{$fechaDestino} ({$hrs}h {$mins}m)";
                    })
                    ->join(', ');

                $hrsCedidas = intdiv($minutosYaCedidos, 60);
                $minsCedidas = $minutosYaCedidos % 60;

                throw new Exception(
                    "Este día generó {$hrsCedidas}h {$minsCedidas}m de horas acumuladas que ya fueron consumidas en: {$detalle}. " .
                    "Debes desvincular o ajustar esos usos antes de reducir las horas de este día."
                );
            }
        }

        DB::transaction(function () use ($resumen, $fecha, $data, $mapaCampos, $horaInicioAlmuerzo, $horaFinAlmuerzo) {
            $this->registros->reemplazarRegistros(
                $resumen,
                $fecha,
                $data,
                $mapaCampos
            );

            $this->consolidador->consolidar(
                $resumen,
                $horaInicioAlmuerzo,
                $horaFinAlmuerzo
            );


        });

        return $conflictos;
    }

    /**
     * Registra el uso de horas acumuladas y reconsolida.
     */
    public function ejecutarUsoAcumulado(ResumenJornada $resumen, string $horaInicio, string $horaFin): void
    {
        $inicio = Carbon::parse($horaInicio);
        $fin = Carbon::parse($horaFin);

        if ($fin->lte($inicio)) {
            throw new Exception('La hora final debe ser mayor a la hora de inicio.');
        }

        $minutosAUsar = $inicio->diffInMinutes($fin);
        $disponibles = $this->acumulacion->minutosDisponibles($resumen);

        if ($minutosAUsar > $disponibles) {
            throw new Exception("Solo tienes {$resumen->disponible_formateado} disponibles.");
        }

        // La restricción real: jornal actual + lo que quieres agregar no puede superar 480
        $minutosJornalActual = $resumen->minutos_jornal; // ya consolidado, sin acumulados
        $faltanParaCompletar = max(0, 480 - $minutosJornalActual);

        if ($minutosAUsar > $faltanParaCompletar) {
            $formateado = intdiv($faltanParaCompletar, 60) . "h " . ($faltanParaCompletar % 60) . "m";
            throw new Exception(
                "Solo puedes agregar hasta {$formateado} acumuladas para completar las 8h de jornal."
            );
        }
        $yaExiste = RegistroDiario::where('consolidado_id', $resumen->id)
            ->where('por_acumulacion', true)
            ->exists();

        if ($yaExiste) {
            throw new Exception('Ya existe un registro de uso de horas acumuladas para este día.');
        }

        DB::transaction(function () use ($resumen, $inicio, $fin, $minutosAUsar) {
            RegistroDiario::create([
                'consolidado_id' => $resumen->id,
                'campo' => 'FDM',
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fin' => $fin->format('H:i'),
                'fecha' => $resumen->fecha,
                'documento' => '',
                'regador' => '',
                'tipo_labor' => 'Por Acumulación',
                'descripcion' => 'Uso de horas acumuladas',
                'por_acumulacion' => true,
                'campo_campania_id' => null,
            ]);

            $this->acumulacion->consumirFIFO($resumen, $minutosAUsar);
            $this->consolidador->consolidar($resumen);
        });
    }

    /**
     * Quita el uso de horas acumuladas y reconsolida.
     */
    public function ejecutarQuitarAcumulado(ResumenJornada $resumen, int $registroId): void
    {
        $registro = RegistroDiario::where('id', $registroId)
            ->where('consolidado_id', $resumen->id)
            ->where('por_acumulacion', true)
            ->firstOrFail();

        DB::transaction(function () use ($resumen, $registro) {
            $this->acumulacion->liberarUsos($resumen);
            $registro->delete();
            $this->consolidador->consolidar($resumen);
        });
    }

    // --- helpers privados ---

    private function calcularMinutosBrutos(array $data): int
    {
        $intervalos = [];
        foreach ($data as $row) {
            if (empty($row[0]) || (isset($row[6]) && $row[6]))
                continue; // omitir sh
            $intervalos[] = [
                'hora_inicio' => FormatoHelper::normalizarHora($row[1] ?? '00:00'),
                'hora_fin' => FormatoHelper::normalizarHora($row[2] ?? '00:00'),
            ];
        }
        return empty($intervalos) ? 0 : CalculoHelper::calcularMinutosJornalParcial($intervalos);
    }

}