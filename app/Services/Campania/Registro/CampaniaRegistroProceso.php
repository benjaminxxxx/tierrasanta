<?php

namespace App\Services\Campania\Registro;

use App\Exceptions\ErrorControlado;
use App\Models\CampoCampania;
use App\Services\Costos\Consolidacion\BddManoObraServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use App\Services\Riego\RiegoCampaniaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crear, modificar, cerrar y eliminar campañas.
 *
 * - Valida nombre, solapamiento y cobertura (ningún día con actividades puede quedar sin campaña).
 * - Guarda con CampaniaRegistroCrud (auditado: quién y qué cambió; lo eliminado queda en `auditorias`).
 * - Si cambió el rango de fechas: regenera la mano de obra de los días afectados y reasigna el riego. Los ingresos
 *   de cochinilla y las infestaciones los reasignan los triggers de campos_campanias.
 *
 * Antes de cambiar fechas, la pantalla debe mostrar CampaniaRegistroImpactoConsulta::analizar() y pedir confirmación.
 */
class CampaniaRegistroProceso
{
    /** Campos que solo se cambian con cambiarFechas() / cerrar(). */
    private const CAMPOS_DE_FECHAS = ['campo', 'fecha_inicio', 'fecha_fin'];

    public function __construct(
        private CampaniaRegistroValidador $validador,
        private CampaniaRegistroImpactoConsulta $impacto,
        private CampaniaRegistroCrud $crud,
    ) {
    }

    /** @throws ValidationException */
    public function crear(array $data): CampoCampania
    {
        $data = $this->normalizar($data);
        $this->validador->validar($data);
        $this->validador->validarCobertura($data['campo'], $data['fecha_inicio'], $data['fecha_fin']);

        return DB::transaction(function () use ($data) {
            $campania = $this->crud->crear($data);
            $this->regenerar($campania, [[$data['fecha_inicio'], $data['fecha_fin'] ?? Carbon::today()->toDateString()]]);
            return $campania;
        });
    }

    /** Datos generales y de detalle (infestación, cosecha…). Las fechas no se tocan aquí. */
    public function actualizarDatos(int $campaniaId, array $data): CampoCampania
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $data = array_diff_key($this->normalizar($data), array_flip(self::CAMPOS_DE_FECHAS));

        $this->validador->validar(array_merge($data, [
            'campo' => $campania->campo,
            'fecha_inicio' => $campania->fecha_inicio->toDateString(),
            'fecha_fin' => $campania->fecha_fin?->toDateString(),
        ]), $campania->id);

        return $this->crud->actualizar($campania, $data);
    }

    /** @throws ValidationException */
    public function cambiarFechas(int $campaniaId, string $inicio, ?string $fin, ?string $observacion = null): CampoCampania
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $inicio = Carbon::parse($inicio)->toDateString();
        $fin = $fin ? Carbon::parse($fin)->toDateString() : null;

        $this->validador->validar([
            'campo' => $campania->campo,
            'nombre_campania' => $campania->nombre_campania,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
        ], $campania->id);
        $this->validador->validarCobertura($campania->campo, $inicio, $fin, $campania->id);

        $analisis = $this->impacto->analizar($campania, $inicio, $fin);
        if (!$analisis['hay_cambios']) {
            return $campania;
        }

        return DB::transaction(function () use ($campania, $inicio, $fin, $analisis, $observacion) {
            $antes = formatear_fecha($campania->fecha_inicio) . ' – ' . ($campania->fecha_fin ? formatear_fecha($campania->fecha_fin) : 'abierta');
            $despues = formatear_fecha($inicio) . ' – ' . ($fin ? formatear_fecha($fin) : 'abierta');

            $this->crud->actualizar($campania, ['fecha_inicio' => $inicio, 'fecha_fin' => $fin],
                $observacion ?? "Cambio de fechas: {$antes} → {$despues}");

            // Solo los días que de verdad cambian de campaña (rango efectivo) y solo en este campo
            $this->regenerar($campania, array_merge($analisis['salen'], $analisis['entran']));
            return $campania;
        });
    }

    /** Cierra una campaña vigente (normalmente el último día de su cosecha). */
    public function cerrar(int $campaniaId, string $fechaCierre): CampoCampania
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        if ($campania->fecha_fin) {
            throw ValidationException::withMessages([
                'fecha_cierre' => "La campaña ya está cerrada desde el " . formatear_fecha($campania->fecha_fin) . '.',
            ]);
        }

        return $this->cambiarFechas($campania->id, $campania->fecha_inicio->toDateString(), $fechaCierre,
            'Cierre de campaña el ' . formatear_fecha($fechaCierre));
    }

    /**
     * Plan para corregir la fecha de inicio y/o de cierre (o reabrir). Si la campaña vecina estaba pegada (la siguiente
     * empezaba el día después del cierre, o la anterior terminaba el día antes del inicio) o quedaría encimada, y
     * $moverVecinas, la vecina se mueve con ella para que sigan pegadas. No guarda nada.
     *
     * @return array{cambios: array<int, array{campania: CampoCampania, inicio: string, fin: ?string}>, avisos: string[], vecinas: array}
     * @throws ValidationException
     */
    public function planCorreccionFechas(int $campaniaId, string $inicio, ?string $fin, bool $moverVecinas = true): array
    {
        $campania = CampoCampania::findOrFail($campaniaId);
        $inicio = Carbon::parse($inicio)->toDateString();
        $fin = $fin ? Carbon::parse($fin)->toDateString() : null;
        $viejoInicio = $campania->fecha_inicio->toDateString();
        $viejoFin = $campania->fecha_fin?->toDateString();
        if ($fin && $fin < $inicio) {
            throw ValidationException::withMessages(['fecha_fin' => 'La fecha de cierre debe ser igual o posterior a la de inicio.']);
        }

        $anterior = CampoCampania::where('campo', $campania->campo)->where('id', '<>', $campania->id)
            ->whereDate('fecha_inicio', '<', $viejoInicio)->orderByDesc('fecha_inicio')->first();
        $siguiente = CampoCampania::where('campo', $campania->campo)->where('id', '<>', $campania->id)
            ->whereDate('fecha_inicio', '>', $viejoInicio)->orderBy('fecha_inicio')->first();

        $cambios = [$campania->id => ['campania' => $campania, 'inicio' => $inicio, 'fin' => $fin]];
        $vecinas = [];

        if ($siguiente) {
            $sigInicio = $siguiente->fecha_inicio->toDateString();
            if ($fin === null) {
                throw ValidationException::withMessages(['fecha_fin' => "No se puede reabrir: después está la campaña {$siguiente->nombre_campania} "
                    . '(desde ' . formatear_fecha($sigInicio) . '). Solo se reabre la última campaña del campo.']);
            }
            $pegada = $viejoFin && $sigInicio === Carbon::parse($viejoFin)->addDay()->toDateString();
            $encima = $fin >= $sigInicio;
            $nuevoSigInicio = Carbon::parse($fin)->addDay()->toDateString();
            if (($pegada || $encima) && $nuevoSigInicio !== $sigInicio) {
                if (!$moverVecinas && $encima) {
                    throw ValidationException::withMessages(['fecha_fin' => "El cierre se cruza con {$siguiente->nombre_campania}, que empieza el " . formatear_fecha($sigInicio) . '.']);
                }
                if ($moverVecinas) {
                    if ($siguiente->fecha_fin && $nuevoSigInicio > $siguiente->fecha_fin->toDateString()) {
                        throw ValidationException::withMessages(['fecha_fin' => "El cierre deja sin días a {$siguiente->nombre_campania} (termina el " . formatear_fecha($siguiente->fecha_fin) . ').']);
                    }
                    $cambios[$siguiente->id] = ['campania' => $siguiente, 'inicio' => $nuevoSigInicio, 'fin' => $siguiente->fecha_fin?->toDateString()];
                }
                $vecinas['siguiente'] = ['nombre' => $siguiente->nombre_campania, 'antes' => $sigInicio, 'despues' => $nuevoSigInicio, 'se_mueve' => $moverVecinas];
            }
        }

        if ($anterior && $anterior->fecha_fin) {
            $antFin = $anterior->fecha_fin->toDateString();
            $pegada = $antFin === Carbon::parse($viejoInicio)->subDay()->toDateString();
            $encima = $inicio <= $antFin;
            $nuevoAntFin = Carbon::parse($inicio)->subDay()->toDateString();
            if (($pegada || $encima) && $nuevoAntFin !== $antFin) {
                if (!$moverVecinas && $encima) {
                    throw ValidationException::withMessages(['fecha_inicio' => "El inicio se cruza con {$anterior->nombre_campania}, que termina el " . formatear_fecha($antFin) . '.']);
                }
                if ($moverVecinas) {
                    if ($nuevoAntFin < $anterior->fecha_inicio->toDateString()) {
                        throw ValidationException::withMessages(['fecha_inicio' => "El inicio deja sin días a {$anterior->nombre_campania} (empieza el " . formatear_fecha($anterior->fecha_inicio) . ').']);
                    }
                    $cambios[$anterior->id] = ['campania' => $anterior, 'inicio' => $anterior->fecha_inicio->toDateString(), 'fin' => $nuevoAntFin];
                }
                $vecinas['anterior'] = ['nombre' => $anterior->nombre_campania, 'antes' => $antFin, 'despues' => $nuevoAntFin, 'se_mueve' => $moverVecinas];
            }
        }

        // Validación del estado final: cada una contra las demás (las que también cambian se comparan con su rango nuevo)
        $ids = array_keys($cambios);
        foreach ($cambios as $id => $c) {
            $this->validador->validar(['campo' => $c['campania']->campo, 'nombre_campania' => $c['campania']->nombre_campania,
                'fecha_inicio' => $c['inicio'], 'fecha_fin' => $c['fin']], $id, $ids);
            foreach ($cambios as $otroId => $o) {
                if ($otroId !== $id && $c['inicio'] <= ($o['fin'] ?? '9999-12-31') && ($c['fin'] ?? '9999-12-31') >= $o['inicio']) {
                    throw ValidationException::withMessages(['fecha_inicio' => "{$c['campania']->nombre_campania} y {$o['campania']->nombre_campania} quedarían encimadas."]);
                }
            }
        }

        $avisos = [];
        foreach ($cambios as $c) {
            foreach ($this->impacto->analizar($c['campania'], $c['inicio'], $c['fin'])['avisos'] as $a) {
                $avisos[] = (count($cambios) > 1 ? "{$c['campania']->nombre_campania}: " : '') . $a;
            }
        }
        $sinCambio = $inicio === $viejoInicio && $fin === $viejoFin;
        return ['cambios' => $cambios, 'avisos' => $avisos, 'vecinas' => $vecinas, 'sin_cambio' => $sinCambio];
    }

    /**
     * Corrige la fecha de inicio y/o de cierre (o reabre) con motivo; mueve la vecina pegada si corresponde.
     * Todo en una transacción: si queda algún día con actividades sin campaña, no se guarda nada.
     *
     * @throws ValidationException
     */
    public function corregirFechas(int $campaniaId, string $inicio, ?string $fin, string $motivo, bool $moverVecinas = true): array
    {
        if (trim($motivo) === '') {
            throw ValidationException::withMessages(['motivo' => 'Indica el motivo de la corrección.']);
        }
        $plan = $this->planCorreccionFechas($campaniaId, $inicio, $fin, $moverVecinas);
        if ($plan['sin_cambio']) {
            return $plan;
        }
        $tramos = [];
        foreach ($plan['cambios'] as $c) {
            $a = $this->impacto->analizar($c['campania'], $c['inicio'], $c['fin']);
            $tramos = array_merge($tramos, $a['salen'], $a['entran']);
        }

        DB::transaction(function () use ($plan, $motivo, $tramos) {
            // Primero las que se achican (para no encimarse), luego las que crecen
            $orden = collect($plan['cambios'])->sortBy(fn($c) => ($c['fin'] !== null && $c['campania']->fecha_fin && $c['fin'] < $c['campania']->fecha_fin->toDateString())
                || $c['inicio'] > $c['campania']->fecha_inicio->toDateString() ? 0 : 1);
            foreach ($orden as $c) {
                $campania = $c['campania'];
                $antes = formatear_fecha($campania->fecha_inicio) . ' – ' . ($campania->fecha_fin ? formatear_fecha($campania->fecha_fin) : 'abierta');
                $despues = formatear_fecha($c['inicio']) . ' – ' . ($c['fin'] ? formatear_fecha($c['fin']) : 'abierta');
                $this->crud->actualizar($campania, ['fecha_inicio' => $c['inicio'], 'fecha_fin' => $c['fin']],
                    "Corrección de fechas: {$antes} → {$despues}. Motivo: " . trim($motivo));
            }
            // Ningún día con actividades sin campaña (ya con las fechas nuevas)
            foreach ($plan['cambios'] as $id => $c) {
                $this->validador->validarCobertura($c['campania']->campo, $c['inicio'], $c['fin'], $id);
            }
            $primera = collect($plan['cambios'])->first()['campania'];
            $this->regenerar($primera, $tramos);
        });
        return $plan;
    }

    public function eliminar(int $campaniaId): void
    {
        $campania = CampoCampania::findOrFail($campaniaId);

        // Solo se elimina una campaña creada por error y sin datos. Con registros vinculados se corrigen sus fechas.
        $vinculos = $this->impacto->registrosVinculados($campania);
        if ($vinculos) {
            $detalle = collect($vinculos)->map(fn($n, $desc) => number_format($n) . " {$desc}")->implode(', ');
            throw new ErrorControlado("No se puede eliminar la campaña {$campania->nombre_campania} del campo {$campania->campo}: "
                . "ya tiene {$detalle}. Si sus fechas están mal, corrígelas (Editar campaña) en lugar de eliminarla.");
        }

        // Rango efectivo (una abierta termina donde empieza la siguiente), como en el consolidado
        $rango = [
            $campania->fecha_inicio->toDateString(),
            ConsolidarCostoManoObraServicio::finEfectivoCampania($campania) ?? Carbon::today()->toDateString(),
        ];

        DB::transaction(function () use ($campania, $rango) {
            $this->crud->eliminar($campania);
            $this->regenerarManoObra([$rango], $campania->campo);
        });
    }

    /** Mano de obra de los días que cambiaron de campaña y riego del campo. */
    private function regenerar(CampoCampania $campania, array $tramos): void
    {
        $this->regenerarManoObra($tramos, $campania->campo);

        // El riego guarda la campaña y no tiene trigger: se reasigna en esta campaña y sus vecinas
        CampoCampania::where('campo', $campania->campo)
            ->orderBy('fecha_inicio')
            ->get()
            ->filter(fn($c) => $this->tocaTramos($c, $tramos))
            ->each(fn($c) => RiegoCampaniaServicio::procesarRiegosParaCampania($c));
    }

    private function regenerarManoObra(array $tramos, string $campo): void
    {
        $hoy = Carbon::today()->toDateString();
        foreach ($tramos as [$desde, $hasta]) {
            $hasta = min($hasta, $hoy);
            if ($desde <= $hasta) {
                BddManoObraServicio::registrarCambio($desde, $hasta, $campo);
            }
        }
    }

    private function tocaTramos(CampoCampania $campania, array $tramos): bool
    {
        $inicio = $campania->fecha_inicio->toDateString();
        $fin = $campania->fecha_fin?->toDateString() ?? '9999-12-31';
        foreach ($tramos as [$desde, $hasta]) {
            if ($inicio <= $hasta && $fin >= Carbon::parse($desde)->subDay()->toDateString()) {
                return true;
            }
        }
        return false;
    }

    private function normalizar(array $data): array
    {
        if (array_key_exists('nombre_campania', $data)) {
            $data['nombre_campania'] = mb_strtoupper(trim((string) $data['nombre_campania']));
        }
        if (array_key_exists('fecha_fin', $data) || array_key_exists('fecha_inicio', $data)) {
            $data['fecha_fin'] = ($data['fecha_fin'] ?? null) ?: null;
        }
        return $data;
    }
}
