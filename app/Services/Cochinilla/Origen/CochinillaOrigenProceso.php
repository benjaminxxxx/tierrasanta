<?php

namespace App\Services\Cochinilla\Origen;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoOrigen;
use App\Models\CochinillaObservacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Campaña dueña de la cochinilla de un ingreso (a la que se le carga la venta).
 *
 * - Cosecha, pre-cosecha, poda, mamá…: la cochinilla es del campo cosechado → su propia campaña.
 * - Infestadores (cajitas, tubos, malla): se registran con el CAMPO QUE DIO LA MAMÁ, no con el campo donde se
 *   recogieron (así lo lleva el Excel de cochinilla; confirmado por Flavia el 11/11/2026 y por los datos: en 160 de
 *   162 lotes de TSH el campo había dado mamá 0 a 45 días antes). Ej.: A5 se cosechó (Poda Mama 04–13/03/2026), su
 *   mamá infestó Limonero4, 18, B3 y A8 (06–14/03) y esos infestadores volvieron como lotes 1502–1506 "campo A5".
 *   El dueño es el mismo campo del lote, en la campaña que se cosechó para ese envío de mamá (la del ingreso de
 *   mamá de esos días, o la que cerró con esa cosecha). Un lote = un solo campo: no se reparte.
 *   La mano de obra de infestar y de recoger sigue siendo de los campos infestados (viene de planilla/cuadrilla).
 */
class CochinillaOrigenProceso
{
    /** Días máximos entre el envío de mamá y el retorno de sus infestadores (en los datos: hasta 45). */
    public const DIAS_RETORNO = 90;
    /** Ventana para buscar el ingreso de una venta por su campo (VentaServicio). */
    public const DIAS_INFESTADORES = 180;
    /** Infestaciones con hasta estos días de diferencia son el mismo envío / la misma tanda. */
    public const DIAS_MISMA_INFESTACION = 15;
    /** Días antes del envío en que se busca el ingreso de mamá del campo de origen. */
    public const DIAS_COSECHA_MAMA = 20;

    private ?array $observacionesMama = null;

    public static function esDeInfestador(?string $observacion): bool
    {
        return $observacion !== null && str_starts_with($observacion, 'infestador');
    }

    /** Calcula y guarda el origen de un ingreso y su fila de origen (cochinilla_ingreso_origenes). */
    public function recalcular(CochinillaIngreso $ingreso): void
    {
        $datos = $this->resolver($ingreso);
        $ingreso->forceFill($datos)->saveQuietly();
        $this->sincronizarOrigen($ingreso, $datos);
    }

    /** @return int ingresos recalculados */
    public function recalcularTodos(): int
    {
        $n = 0;
        CochinillaIngreso::query()->orderBy('id')->chunkById(200, function ($ingresos) use (&$n) {
            foreach ($ingresos as $ingreso) {
                $this->recalcular($ingreso);
                $n++;
            }
        });
        return $n;
    }

    /** Al guardar infestaciones con mamá de $campo: sus ingresos de infestador pueden cambiar de campaña dueña. */
    public function recalcularPorCampo(string $campo): void
    {
        CochinillaIngreso::where('observacion', 'like', 'infestador%')->where('campo', $campo)->get()->each(fn($i) => $this->recalcular($i));
    }

    /** @return array{campo_origen:?string, campania_origen_id:?int, origen_estado:string, origen_detalle:?string} */
    public function resolver(CochinillaIngreso $ingreso): array
    {
        $propio = [
            'campo_origen' => $ingreso->campo,
            'campania_origen_id' => $ingreso->campo_campania_id,
            'origen_estado' => 'propio',
            'origen_detalle' => null,
        ];
        if (!self::esDeInfestador($ingreso->observacion)) {
            return $propio;
        }

        $envio = $this->envio($ingreso->campo, Carbon::parse($ingreso->fecha)->toDateString());
        if (!$envio) {
            return ['origen_estado' => 'sin_infestacion', 'origen_detalle' => mb_substr("{$ingreso->campo} no dio mamá para infestar en los "
                . self::DIAS_RETORNO . ' días anteriores a este lote: se deja la campaña del campo por fecha. Revisar el campo o la fecha del lote.', 0, 300)] + $propio;
        }

        $campania = $this->campaniaOrigen($ingreso->campo, $envio['fin']);
        $fmt = fn($f) => Carbon::parse($f)->format('d/m/Y');
        return [
            'campo_origen' => $ingreso->campo,
            'campania_origen_id' => $campania?->id ?? $ingreso->campo_campania_id,
            'origen_estado' => 'infestacion',
            'origen_detalle' => mb_substr("Mamá de {$ingreso->campo} que infestó " . implode(', ', array_keys($envio['destinos']))
                . ' (' . number_format($envio['kg'], 2) . ' kg, ' . ($envio['inicio'] === $envio['fin'] ? $fmt($envio['inicio']) : $fmt($envio['inicio']) . ' al ' . $fmt($envio['fin']))
                . '): estos infestadores volvieron de esos campos.' . ($campania ? '' : ' No se encontró la campaña cosechada; queda la del campo por fecha.'), 0, 300),
        ];
    }

    /**
     * El envío de mamá de un campo del que vuelve un lote: la última vez que el campo dio mamá (hasta DIAS_RETORNO
     * días antes), con las infestaciones seguidas de ese envío (separadas por menos de DIAS_MISMA_INFESTACION).
     *
     * @return array{inicio:string, fin:string, kg:float, destinos: array<string, float>}|null
     */
    public function envio(string $campo, string $fecha): ?array
    {
        $ultima = CochinillaInfestacion::where('campo_origen_nombre', $campo)
            ->whereBetween('fecha', [Carbon::parse($fecha)->subDays(self::DIAS_RETORNO)->toDateString(), $fecha])
            ->max('fecha');
        if (!$ultima) {
            return null;
        }
        $previas = CochinillaInfestacion::where('campo_origen_nombre', $campo)->where('fecha', '<=', $ultima)
            ->orderByDesc('fecha')->orderByDesc('id')->get(['fecha', 'campo_nombre', 'kg_madres']);
        $filas = [];
        $anterior = null;
        foreach ($previas as $inf) {
            if ($anterior && Carbon::parse($inf->fecha)->diffInDays(Carbon::parse($anterior)) > self::DIAS_MISMA_INFESTACION) {
                break;
            }
            $filas[] = $inf;
            $anterior = $inf->fecha;
        }
        $destinos = [];
        foreach (array_reverse($filas) as $inf) {
            $destinos[$inf->campo_nombre] = ($destinos[$inf->campo_nombre] ?? 0) + (float) $inf->kg_madres;
        }
        return [
            'inicio' => Carbon::parse(end($filas)->fecha)->toDateString(),
            'fin' => Carbon::parse($ultima)->toDateString(),
            'kg' => round(array_sum($destinos), 2),
            'destinos' => $destinos,
        ];
    }

    /** Campaña cosechada de un campo para un envío de mamá (público para la recuperación y la historia). */
    public function campaniaParaOrigen(string $campoOrigen, string $fechaEnvio): ?CampoCampania
    {
        return $this->campaniaOrigen($campoOrigen, $fechaEnvio);
    }

    /**
     * Una sola fila de origen por lote de infestador (el informe mensual, la historia y la corrección de fechas de
     * campañas la leen): su campo y su campaña dueña, con todos sus kilos. Los demás ingresos no tienen fila.
     */
    private function sincronizarOrigen(CochinillaIngreso $ingreso, array $datos): void
    {
        $ingreso->origenes()->delete();
        if (!self::esDeInfestador($ingreso->observacion) || $datos['origen_estado'] !== 'infestacion' || (float) $ingreso->total_kilos <= 0) {
            return;
        }
        CochinillaIngresoOrigen::create([
            'cochinilla_ingreso_id' => $ingreso->id,
            'campo_origen' => $datos['campo_origen'],
            'campania_origen_id' => $datos['campania_origen_id'],
            'kg' => round((float) $ingreso->total_kilos, 2),
            'kg_madres' => null,
            'porcentaje_sugerido' => 100,
            'registrado_por' => null,
        ]);
    }

    /**
     * La campaña del campo de origen que se cosechó para ese envío: la del ingreso de mamá de esos días; si no hay,
     * la que cerró con esa cosecha, o la que corría antes del envío.
     */
    private function campaniaOrigen(?string $campo, $fechaEnvio): ?CampoCampania
    {
        if (!$campo) {
            return null;
        }
        $f = Carbon::parse($fechaEnvio)->startOfDay();
        $this->observacionesMama ??= CochinillaObservacion::where('es_cosecha_mama', true)->pluck('codigo')->all();

        $ingresoMama = CochinillaIngreso::where('campo', $campo)
            ->whereIn('observacion', $this->observacionesMama)
            ->whereBetween('fecha', [$f->copy()->subDays(self::DIAS_COSECHA_MAMA)->toDateString(), $f->toDateString()])
            ->whereNotNull('campo_campania_id')
            ->orderByDesc('fecha')->first();
        if ($ingresoMama) {
            return $ingresoMama->campoCampania;
        }

        // La campaña que cerró con esa cosecha (la siguiente pudo empezar antes del envío)
        $cerrada = CampoCampania::where('campo', $campo)
            ->whereBetween(DB::raw('COALESCE(cosch_fecha, fecha_fin)'), [$f->copy()->subDays(self::DIAS_COSECHA_MAMA)->toDateString(), $f->copy()->addDays(5)->toDateString()])
            ->orderByDesc(DB::raw('COALESCE(cosch_fecha, fecha_fin)'))->first();

        return $cerrada ?? CampoCampania::where('campo', $campo)->whereDate('fecha_inicio', '<', $f)
            ->orderByDesc('fecha_inicio')->first();
    }
}
