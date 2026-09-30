<?php

namespace App\Services\Campania\Registro;

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

    public function eliminar(int $campaniaId): void
    {
        $campania = CampoCampania::findOrFail($campaniaId);

        if ($campania->evaluacionPoblacionPlantas()->exists()) {
            throw new \RuntimeException('No se puede eliminar la campaña porque tiene evaluaciones de población de plantas.');
        }
        if ($campania->distribucionesCostosMensuales()->exists()) {
            throw new \RuntimeException('No se puede eliminar la campaña porque tiene distribuciones de costos mensuales.');
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
