<?php

namespace App\Services\Costos\Mensual;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CostoMensual;
use App\Models\CostoMensualDistribucion;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use App\Services\Campania\ValidadorRangosCampania;
use Illuminate\Support\Carbon;

/**
 * Costos → Mensual (solo lectura): campañas que reciben costo en el mes, costos fijos y operativos del mes y la
 * distribución guardada.
 */
class CostosMensualConsulta
{
    /**
     * Una fila por campaña que toca el mes; los campos sin campaña en el mes van con campania_id null.
     * Los campos bloqueados solo aparecen si tienen una campaña en el mes (su costo se sigue distribuyendo).
     *
     * @return array{filas: array<int, array>, bloqueados: array<int, array>, avisos: array<int, array>}
     */
    public function campanias(int $anio, int $mes): array
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth();
        $diasMes = $inicioMes->daysInMonth;

        // Avisos de rangos (superposición, campaña abierta con otra posterior) por id de campaña
        $avisos = ValidadorRangosCampania::validarCampos();
        $avisosPorCampania = [];
        foreach ($avisos as $campo) {
            foreach ($campo['errores'] as $error) {
                foreach ($error['campania_ids'] ?? [$error['campania_id']] as $id) {
                    $avisosPorCampania[$id][] = ['tipo' => $error['tipo'], 'mensaje' => $error['mensaje']];
                }
            }
        }

        $campos = Campo::with(['campanias' => fn($q) => $q
            ->where('fecha_inicio', '<=', $finMes)
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes))
            ->orderBy('fecha_inicio')])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $estados = app(CampaniaCosechaConsulta::class)->estados(
            $campos->flatMap->campanias->filter(fn($c) => $c->fecha_fin === null)
        );

        $filas = [];
        $bloqueados = [];
        foreach ($campos as $campo) {
            $base = ['campo' => $campo->nombre, 'area' => (float) $campo->area, 'bloqueado' => (bool) $campo->bloqueado];

            if ($campo->campanias->isEmpty()) {
                $fila = $base + ['campania_id' => null, 'campania' => null, 'fecha_inicio' => null, 'fecha_fin' => null,
                    'dias' => 0, 'dias_mes' => $diasMes, 'por_cerrar' => null, 'avisos' => []];
                $campo->bloqueado ? $bloqueados[] = $fila : $filas[] = $fila;
                continue;
            }

            foreach ($campo->campanias as $c) {
                $desde = $c->fecha_inicio->greaterThan($inicioMes) ? $c->fecha_inicio : $inicioMes;
                $hasta = $c->fecha_fin && $c->fecha_fin->lessThan($finMes) ? $c->fecha_fin : $finMes;
                $estado = $estados[$c->id] ?? null;

                $filas[] = $base + [
                    'campania_id' => $c->id,
                    'campania' => $c->nombre_campania,
                    'fecha_inicio' => $c->fecha_inicio->toDateString(),
                    'fecha_fin' => $c->fecha_fin?->toDateString(),
                    'dias' => (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1,
                    'dias_mes' => $diasMes,
                    // Cosecha terminada: conviene cerrar (actividades | tiempo)
                    'por_cerrar' => $estado['recomendar_cierre'] ?? null,
                    'avisos' => $avisosPorCampania[$c->id] ?? [],
                ];
            }
        }

        return ['filas' => $filas, 'bloqueados' => $bloqueados, 'avisos' => array_values($avisos)];
    }

    /** Datos de la tarjeta de una campaña: fechas, días en el mes, estado de cosecha y avisos. */
    public function tarjeta(int $campaniaId, int $anio, int $mes): ?array
    {
        $c = CampoCampania::find($campaniaId);
        if (!$c) {
            return null;
        }
        $fila = collect($this->campanias($anio, $mes)['filas'])->firstWhere('campania_id', $c->id);
        $estado = app(CampaniaCosechaConsulta::class)->estado($c);

        return [
            'id' => $c->id,
            'campo' => $c->campo,
            'nombre' => $c->nombre_campania,
            'area' => $c->area !== null ? (float) $c->area : null,
            'fecha_inicio' => $c->fecha_inicio->toDateString(),
            'fecha_fin' => $c->fecha_fin?->toDateString(),
            'dias_campania' => (int) $c->fecha_inicio->copy()->startOfDay()->diffInDays(($c->fecha_fin ?? Carbon::today())->copy()->startOfDay()) + 1,
            'dias_mes' => $fila['dias'] ?? 0,
            'total_dias_mes' => $fila['dias_mes'] ?? Carbon::create($anio, $mes, 1)->daysInMonth,
            'cosecha' => $estado,
            'avisos' => $fila['avisos'] ?? [],
        ];
    }

    /**
     * Costos del mes por concepto (para la tabla editable).
     *
     * @return array{filas: array<int, array>, existe: bool, moi_calculado: ?array, distribuido_en: ?string}
     */
    public function costos(int $anio, int $mes): array
    {
        $costo = CostoMensual::where('anio', $anio)->where('mes', $mes)->first();

        $filas = [];
        foreach (CostosMensualReglas::CONCEPTOS as $col => [$tipo, $nombre]) {
            $blanco = (float) ($costo?->{"{$col}_blanco"} ?? 0);
            $negro = (float) ($costo?->{"{$col}_negro"} ?? 0);
            $filas[] = [
                'concepto' => $col,
                'tipo' => CostosMensualReglas::nombreTipo($tipo),
                'nombre' => $nombre,
                'blanco' => $blanco,
                'negro' => $negro,
                'total' => round($blanco + $negro, 2),
                // MOI calculada: solo cambia al recalcular en /costos/mano-obra-indirecta
                'bloqueado' => $col === CostosMensualReglas::MOI && (bool) $costo?->moi_calculado_en,
            ];
        }

        $distribuidoEn = $costo ? CostoMensualDistribucion::where('costo_mensual_id', $costo->id)->max('updated_at') : null;

        return [
            'filas' => $filas,
            'existe' => (bool) $costo,
            'costo_mensual_id' => $costo?->id,
            'moi_calculado' => $costo?->moi_calculado_en ? [
                'en' => $costo->moi_calculado_en->format('d/m/Y H:i'),
                'concluyente' => (bool) $costo->moi_concluyente,
            ] : null,
            'distribuido_en' => $distribuidoEn ? Carbon::parse($distribuidoEn)->format('d/m/Y H:i') : null,
        ];
    }

    /** Distribución guardada del mes (una fila por campaña), o [] si aún no se aprobó. */
    public function distribucion(int $anio, int $mes): array
    {
        $costo = CostoMensual::where('anio', $anio)->where('mes', $mes)->first();
        if (!$costo) {
            return [];
        }

        return CostoMensualDistribucion::with('campania:id,campo,nombre_campania,fecha_inicio,fecha_fin')
            ->where('costo_mensual_id', $costo->id)
            ->get()
            ->sortBy(fn($d) => [$d->campania?->campo, $d->campania?->fecha_inicio])
            ->map(fn($d) => [
                'campo' => $d->campania?->campo,
                'campania' => $d->campania?->nombre_campania,
                'fecha_inicio' => $d->campania?->fecha_inicio?->toDateString(),
                'fecha_fin' => $d->campania?->fecha_fin?->toDateString(),
                'dias_mes' => (int) $d->dias_mes,
                'dias_activos' => (int) $d->dias_activos,
                'porcentaje' => (float) $d->porcentaje,
                'montos' => collect(array_keys(CostosMensualReglas::CONCEPTOS))->mapWithKeys(fn($col) => [$col => (float) $d->{$col}])->all(),
            ])
            ->values()
            ->all();
    }
}
