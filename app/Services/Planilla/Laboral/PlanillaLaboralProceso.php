<?php

namespace App\Services\Planilla\Laboral;

use App\Exceptions\ErrorControlado;
use App\Models\Persona;
use App\Models\PlanCargo;
use App\Models\PlanContrato;
use App\Models\PlanContratoAsignacion;
use App\Models\PlanContratoRemuneracion;
use App\Models\PlanEmpleado;
use App\Models\PlanEmpleadoPago;
use App\Models\PlanEmpleadoPension;
use App\Models\PlanParticula;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de escritura del historial laboral (empleado + persona, contratos y partículas). Formularios, Excel y
 * cambios masivos pasan por aquí, así todos validan con PlanillaLaboralReglas. Errores: ErrorControlado con el motivo.
 */
class PlanillaLaboralProceso
{
    // ------------------------------------------------------------------ empleado y persona

    /**
     * Crea o actualiza el empleado y su persona. Datos de la persona: documento, nombres, apellido_paterno,
     * apellido_materno, fecha_nacimiento, genero (M/F), email, numero, direccion. Del empleado: orden, comentarios.
     */
    public function guardarEmpleado(array $d, ?int $empleadoId = null): PlanEmpleado
    {
        $dni = preg_replace('/\s+/', '', (string) ($d['documento'] ?? ''));
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new ErrorControlado('El DNI debe tener 8 dígitos.');
        }
        if (blank($d['nombres'] ?? null)) {
            throw new ErrorControlado('Los nombres del empleado son obligatorios.');
        }
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ErrorControlado("El correo \"{$d['email']}\" no es válido.");
        }
        if (!empty($d['fecha_nacimiento']) && $d['fecha_nacimiento'] >= now()->toDateString()) {
            throw new ErrorControlado('La fecha de nacimiento debe ser anterior a hoy.');
        }

        return DB::transaction(function () use ($d, $dni, $empleadoId) {
            $empleado = $empleadoId ? PlanEmpleado::withTrashed()->findOrFail($empleadoId) : null;
            $otro = PlanEmpleado::withTrashed()->porDocumento($dni)->when($empleado, fn($q) => $q->where('id', '<>', $empleado->id))->first();
            if ($otro) {
                throw new ErrorControlado("El DNI {$dni} ya pertenece a otro empleado ({$otro->nombre_completo}).");
            }

            $datosPersona = [
                'tipo' => 'individual', 'tipo_documento' => 'DNI', 'numero_documento' => $dni,
                'nombres' => trim($d['nombres']), 'apellido_paterno' => $d['apellido_paterno'] ?? null, 'apellido_materno' => $d['apellido_materno'] ?? null,
                'nombre_mostrar' => trim(implode(' ', array_filter([$d['apellido_paterno'] ?? null, $d['apellido_materno'] ?? null, $d['nombres']]))),
                'fecha_nacimiento' => $d['fecha_nacimiento'] ?? null, 'genero' => Persona::generoLargo($d['genero'] ?? null),
                'email' => $d['email'] ?? null, 'telefono_movil' => $d['numero'] ?? null, 'direccion' => $d['direccion'] ?? null,
            ];
            // La persona puede existir ya (cuadrillero, proveedor, usuario): se reutiliza por su DNI
            $persona = $empleado?->persona ?? Persona::where('tipo_documento', 'DNI')->where('numero_documento', $dni)->first();
            if ($persona) {
                $persona->update($datosPersona);
            } else {
                $persona = Persona::create($datosPersona + ['codigo' => 'PLA-' . $dni]);
            }

            $empleado ??= new PlanEmpleado();
            $empleado->fill(['persona_id' => $persona->id, 'orden' => $d['orden'] ?? $empleado->orden, 'comentarios' => $d['comentarios'] ?? null]);
            $empleado->save();
            return $empleado->fresh();
        });
    }

    // ------------------------------------------------------------------ contratos

    /**
     * Crea o actualiza un contrato (cabecera). Valida cruces con los otros contratos del empleado y que sus partículas
     * sigan dentro del contrato si cambian las fechas. Datos: tipo_contrato, tipo_planilla, tipo_ingreso, fecha_inicio,
     * fecha_fin, fecha_fin_prueba, motivo_cese_sunat, comentario_cese.
     */
    public function guardarContrato(PlanEmpleado $empleado, array $d, ?PlanContrato $contrato = null): PlanContrato
    {
        $d = $this->normalizarContrato($d);
        $this->validarContrato($empleado, $d, $contrato);
        if ($contrato) {
            $this->validarParticulasDentro($contrato, $d['fecha_inicio'], $d['fecha_fin']);
            $this->validarRegistrosDiarios($contrato, $d, 'Con ese cambio');
        }
        return DB::transaction(function () use ($empleado, $contrato, $d) {
            $contrato ??= new PlanContrato(['plan_empleado_id' => $empleado->id]);
            $contrato->fill($d);
            $contrato->save();
            $this->actualizarListasMensuales($empleado);
            return $contrato;
        });
    }

    /** Contrato nuevo con su asignación y remuneración iniciales (desde su fecha de inicio). */
    public function contratar(PlanEmpleado $empleado, array $contrato, array $asignacion, array $remuneracion): PlanContrato
    {
        return DB::transaction(function () use ($empleado, $contrato, $asignacion, $remuneracion) {
            $c = $this->guardarContrato($empleado, $contrato);
            $inicio = $c->fecha_inicio->toDateString();
            $this->guardarAsignacion($c, ['desde' => $inicio] + $asignacion);
            $this->guardarRemuneracion($c, ['desde' => $inicio] + $remuneracion);
            return $c;
        });
    }

    /** Cierra el contrato en una fecha (cese). Las partículas posteriores al cese lo impiden. */
    public function finalizarContrato(PlanContrato $contrato, string $fechaFin, ?string $motivoSunat = null, ?string $comentario = null): PlanContrato
    {
        $d = ['fecha_fin' => $fechaFin, 'motivo_cese_sunat' => $motivoSunat, 'comentario_cese' => $comentario, 'finalizado_por' => Auth::id()];
        $this->validarContrato($contrato->empleado, ['fecha_inicio' => $contrato->fecha_inicio->toDateString(), 'fecha_fin_prueba' => $contrato->fecha_fin_prueba?->toDateString()] + $d, $contrato);
        $this->validarParticulasDentro($contrato, $contrato->fecha_inicio->toDateString(), $fechaFin);
        $this->validarRegistrosDiarios($contrato, ['fecha_inicio' => $contrato->fecha_inicio->toDateString(), 'fecha_fin' => $fechaFin,
            'tipo_planilla' => $contrato->tipo_planilla], 'Con ese cese');
        return DB::transaction(function () use ($contrato, $d) {
            $contrato->update($d);
            $this->actualizarListasMensuales($contrato->empleado);
            return $contrato;
        });
    }

    /** Quita el cese: el contrato vuelve a estar abierto (si no se cruza con otro). */
    public function reabrirContrato(PlanContrato $contrato): PlanContrato
    {
        $this->validarContrato($contrato->empleado, ['fecha_inicio' => $contrato->fecha_inicio->toDateString(), 'fecha_fin' => null,
            'fecha_fin_prueba' => $contrato->fecha_fin_prueba?->toDateString()], $contrato);
        return DB::transaction(function () use ($contrato) {
            $contrato->update(['fecha_fin' => null, 'motivo_cese_sunat' => null, 'comentario_cese' => null, 'finalizado_por' => null]);
            $this->actualizarListasMensuales($contrato->empleado);
            return $contrato;
        });
    }

    /** Por seguridad no se elimina un contrato con registros diarios en su periodo: se eliminan esos registros primero. */
    public function eliminarContrato(PlanContrato $contrato): void
    {
        $this->validarRegistrosDiarios($contrato, null, 'No se puede eliminar el contrato:');
        DB::transaction(function () use ($contrato) {
            $contrato->update(['eliminado_por' => Auth::id()]);
            $contrato->delete();
            $this->actualizarListasMensuales($contrato->empleado);
        });
    }

    /**
     * Impide que registros diarios de planilla queden fuera de todo contrato agrario (al eliminar el contrato, acortarlo
     * o cambiarlo de planilla). $nuevo = datos del contrato después del cambio (null = se elimina).
     */
    private function validarRegistrosDiarios(PlanContrato $contrato, ?array $nuevo, string $prefijo): void
    {
        if ($contrato->tipo_planilla !== 'agraria' || !$contrato->exists) {
            return;
        }
        $fechas = PlanillaLaboralConsulta::registrosDiariosSinContrato($contrato, $nuevo);
        if (!$fechas) {
            return;
        }
        $n = count($fechas);
        $rango = $n === 1 ? 'el ' . Carbon::parse($fechas[0])->format('d/m/Y')
            : 'del ' . Carbon::parse($fechas[0])->format('d/m/Y') . ' al ' . Carbon::parse(end($fechas))->format('d/m/Y');
        $nombre = $contrato->empleado?->nombre_completo ?? 'el empleado';
        throw new ErrorControlado(($nuevo ? "{$prefijo} quedarían fuera del contrato {$n} registro(s) diario(s) de {$nombre} ({$rango})."
            : "{$prefijo} {$nombre} tiene {$n} registro(s) diario(s) en su periodo ({$rango}).")
            . ' Por seguridad, elimina primero esos registros en Planilla → Registro diario' . ($nuevo ? ' o elige otras fechas.' : '.'));
    }

    /** El empleado entra (al final) o sale de las listas mensuales del registro diario que ya existen. */
    private function actualizarListasMensuales(?PlanEmpleado $empleado): void
    {
        if ($empleado) {
            app(\App\Services\Planilla\ListaMensual\PlanillaListaMensualProceso::class)->sincronizarEmpleado($empleado);
        }
    }

    private function normalizarContrato(array $d): array
    {
        $fecha = fn($v) => blank($v) ? null : Carbon::parse($v)->toDateString();
        $r = [
            'tipo_contrato' => $d['tipo_contrato'] ?? 'indefinido',
            'tipo_planilla' => $d['tipo_planilla'] ?? 'agraria',
            'tipo_ingreso' => $d['tipo_ingreso'] ?? 'planilla',
            'fecha_inicio' => $fecha($d['fecha_inicio'] ?? null),
            'fecha_fin' => $fecha($d['fecha_fin'] ?? null),
            'fecha_fin_prueba' => $fecha($d['fecha_fin_prueba'] ?? null),
            'motivo_cese_sunat' => blank($d['motivo_cese_sunat'] ?? null) ? null : $d['motivo_cese_sunat'],
            'comentario_cese' => blank($d['comentario_cese'] ?? null) ? null : $d['comentario_cese'],
        ];
        if (!$r['fecha_inicio']) {
            throw new ErrorControlado('La fecha de inicio del contrato es obligatoria.');
        }
        foreach (['tipo_contrato' => PlanillaLaboralReglas::TIPOS_CONTRATO, 'tipo_planilla' => PlanillaLaboralReglas::TIPOS_PLANILLA,
            'tipo_ingreso' => PlanillaLaboralReglas::TIPOS_INGRESO] as $campo => $validos) {
            if (!in_array($r[$campo], $validos, true)) {
                throw new ErrorControlado("\"{$r[$campo]}\" no es válido (" . implode(', ', $validos) . ').');
            }
        }
        return $r;
    }

    private function validarContrato(PlanEmpleado $empleado, array $d, ?PlanContrato $contrato): void
    {
        $lista = $empleado->contratos()->when($contrato?->id, fn($q) => $q->where('id', '<>', $contrato->id))->get()
            ->map(fn($c) => ['ref' => (string) $c->id, 'inicio' => $c->fecha_inicio->toDateString(), 'fin' => $c->fecha_fin?->toDateString(), 'fin_prueba' => null])->all();
        $lista[] = ['ref' => 'este', 'inicio' => $d['fecha_inicio'], 'fin' => $d['fecha_fin'] ?? null, 'fin_prueba' => $d['fecha_fin_prueba'] ?? null];
        $errores = PlanillaLaboralReglas::erroresContratos($lista)['este'] ?? [];
        if ($errores) {
            throw new ErrorControlado(implode(' ', $errores));
        }
    }

    private function validarParticulasDentro(PlanContrato $contrato, string $inicio, ?string $fin): void
    {
        $periodo = ['ref' => 'c', 'inicio' => $inicio, 'fin' => $fin];
        foreach (['asignaciones' => 'La asignación', 'remuneraciones' => 'La remuneración'] as $relacion => $titulo) {
            $p = $contrato->{$relacion}()->get()->map(fn($x) => ['ref' => (string) $x->id, 'desde' => $x->desde->toDateString()])->all();
            $errores = PlanillaLaboralReglas::erroresParticulasDeContrato($p, [$periodo], $titulo);
            if ($errores) {
                throw new ErrorControlado('Con esas fechas quedarían fuera del contrato: ' . implode(' ', array_merge(...array_values($errores)))
                    . ' Corrige o quita esas filas primero.');
            }
        }
    }

    // ------------------------------------------------------------------ partículas del contrato

    /** Datos: desde, plan_cargo_id, grupo_codigo, motivo. Si ya hay una con ese "desde" en el contrato, se reemplaza. */
    public function guardarAsignacion(PlanContrato $contrato, array $d): PlanContratoAsignacion
    {
        $desde = $this->desdeEnContrato($contrato, $d['desde'] ?? null, 'La asignación');
        $this->validarCupo($contrato, $d['plan_cargo_id'] ?? null, $desde);
        return PlanContratoAsignacion::updateOrCreate(['plan_contrato_id' => $contrato->id, 'desde' => $desde], [
            'plan_cargo_id' => $d['plan_cargo_id'] ?? null,
            'grupo_codigo' => blank($d['grupo_codigo'] ?? null) ? null : $d['grupo_codigo'],
            'motivo' => $d['motivo'] ?? null,
        ]);
    }

    /** Datos: desde, sueldo, remuneracion_basica, bonificacion, compensacion_vacacional, modalidad_pago, beneficios_mensuales, motivo. */
    public function guardarRemuneracion(PlanContrato $contrato, array $d): PlanContratoRemuneracion
    {
        $desde = $this->desdeEnContrato($contrato, $d['desde'] ?? null, 'La remuneración');
        $modalidad = $d['modalidad_pago'] ?? 'mensual';
        if (!in_array($modalidad, PlanillaLaboralReglas::MODALIDADES_PAGO, true)) {
            throw new ErrorControlado("Modalidad de pago \"{$modalidad}\" no es válida.");
        }
        $num = fn($v) => blank($v) ? null : round((float) $v, 2);
        foreach (['sueldo', 'remuneracion_basica', 'bonificacion', 'compensacion_vacacional'] as $c) {
            if (($num($d[$c] ?? null) ?? 0) < 0) {
                throw new ErrorControlado('Los montos de la remuneración no pueden ser negativos.');
            }
        }
        return PlanContratoRemuneracion::updateOrCreate(['plan_contrato_id' => $contrato->id, 'desde' => $desde], [
            'sueldo' => $num($d['sueldo'] ?? null), 'remuneracion_basica' => $num($d['remuneracion_basica'] ?? null),
            'bonificacion' => $num($d['bonificacion'] ?? null), 'compensacion_vacacional' => $num($d['compensacion_vacacional'] ?? null),
            'modalidad_pago' => $modalidad, 'beneficios_mensuales' => (bool) ($d['beneficios_mensuales'] ?? false), 'motivo' => $d['motivo'] ?? null,
        ]);
    }

    /** Cargos con cupo (ej. una sola plaza): no se puede asignar si ya está lleno en esa fecha con otros empleados. */
    private function validarCupo(PlanContrato $contrato, $cargoId, string $desde): void
    {
        $cargo = $cargoId ? PlanCargo::find($cargoId) : null;
        if (!$cargo || $cargo->cupo_maximo === null) {
            return;
        }
        $otros = array_diff(PlanillaLaboralConsulta::ocupantesPorCargo($desde)[$cargo->id] ?? [], [$contrato->plan_empleado_id]);
        if (count($otros) >= $cargo->cupo_maximo) {
            $nombres = PlanEmpleado::whereIn('id', $otros)->get()->map->nombre_completo->implode(', ');
            throw new ErrorControlado("El cargo {$cargo->nombre} tiene {$cargo->cupo_maximo} plaza(s) y el " . Carbon::parse($desde)->format('d/m/Y')
                . " ya la(s) ocupa: {$nombres}.");
        }
    }

    private function desdeEnContrato(PlanContrato $contrato, $desde, string $titulo): string
    {
        if (blank($desde)) {
            throw new ErrorControlado("{$titulo}: falta la fecha desde.");
        }
        $desde = Carbon::parse($desde)->toDateString();
        $periodo = ['ref' => 'c', 'inicio' => $contrato->fecha_inicio->toDateString(), 'fin' => $contrato->fecha_fin?->toDateString()];
        $errores = PlanillaLaboralReglas::erroresParticulasDeContrato([['ref' => 'p', 'desde' => $desde]], [$periodo], $titulo);
        if ($errores) {
            throw new ErrorControlado($errores['p'][0]);
        }
        return $desde;
    }

    // ------------------------------------------------------------------ partículas del empleado

    /** Datos: desde, plan_sp_codigo, esta_jubilado, suspension_cuarta, motivo. */
    public function guardarPension(PlanEmpleado $empleado, array $d): PlanEmpleadoPension
    {
        if (blank($d['desde'] ?? null)) {
            throw new ErrorControlado('Pensión: falta la fecha desde.');
        }
        $sp = blank($d['plan_sp_codigo'] ?? null) ? null : $d['plan_sp_codigo'];
        if ($sp && !DB::table('plan_sp_desc')->where('codigo', $sp)->exists()) {
            throw new ErrorControlado("Sistema de pensión \"{$sp}\" no existe.");
        }
        return PlanEmpleadoPension::updateOrCreate(['plan_empleado_id' => $empleado->id, 'desde' => Carbon::parse($d['desde'])->toDateString()], [
            'plan_sp_codigo' => $sp, 'esta_jubilado' => (bool) ($d['esta_jubilado'] ?? false),
            'suspension_cuarta' => (bool) ($d['suspension_cuarta'] ?? false), 'motivo' => $d['motivo'] ?? null,
        ]);
    }

    /** Datos: desde, metodo_pago, banco, tipo_cuenta, moneda_cuenta, numero_cuenta y los de la cuenta secundaria, motivo. */
    public function guardarPago(PlanEmpleado $empleado, array $d): PlanEmpleadoPago
    {
        if (blank($d['desde'] ?? null)) {
            throw new ErrorControlado('Pago: falta la fecha desde.');
        }
        $campos = ['metodo_pago', 'banco', 'tipo_cuenta', 'moneda_cuenta', 'numero_cuenta', 'banco_secundario', 'tipo_cuenta_secundaria',
            'moneda_cuenta_secundaria', 'numero_cuenta_secundaria', 'motivo'];
        $valores = [];
        foreach ($campos as $c) {
            $valores[$c] = blank($d[$c] ?? null) ? null : trim((string) $d[$c]);
        }
        return PlanEmpleadoPago::updateOrCreate(['plan_empleado_id' => $empleado->id, 'desde' => Carbon::parse($d['desde'])->toDateString()], $valores);
    }

    public function eliminarParticula(PlanParticula $particula): void
    {
        $particula->delete();
    }

    // ------------------------------------------------------------------ cambios masivos

    /**
     * Nuevo sueldo para varios empleados desde una fecha: copia la remuneración vigente de su contrato y cambia el sueldo.
     * @param array<int, array{empleado_id: int, nuevo_sueldo: float}> $cambios
     * @return int remuneraciones creadas
     */
    public function cambiarSueldos(array $cambios, string $desde, ?string $motivo = null): int
    {
        if (!$cambios) {
            throw new ErrorControlado('No se proporcionaron cambios para procesar.');
        }
        $desde = Carbon::parse($desde)->toDateString();
        return DB::transaction(function () use ($cambios, $desde, $motivo) {
            $n = 0;
            $sinContrato = [];
            foreach ($cambios as $cambio) {
                $empleado = PlanEmpleado::findOrFail($cambio['empleado_id']);
                $contrato = $empleado->contratoEn($desde);
                if (!$contrato) {
                    $sinContrato[] = $empleado->nombre_completo;
                    continue;
                }
                $actual = $contrato->remuneracionEn($desde);
                $this->guardarRemuneracion($contrato, ['desde' => $desde, 'sueldo' => $cambio['nuevo_sueldo'], 'motivo' => $motivo ?? 'Cambio masivo de sueldo']
                    + ($actual ? $actual->only(['remuneracion_basica', 'bonificacion', 'compensacion_vacacional', 'modalidad_pago', 'beneficios_mensuales']) : []));
                $n++;
            }
            if ($sinContrato) {
                throw new ErrorControlado('No tienen contrato el ' . Carbon::parse($desde)->format('d/m/Y') . ': ' . implode(', ', $sinContrato) . '. No se guardó nada.');
            }
            return $n;
        });
    }
}
