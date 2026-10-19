<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Exceptions\ErrorControlado;
use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoOrigen;
use App\Models\CochinillaRecuperacion;
use App\Services\Cochinilla\Origen\CochinillaOrigenProceso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reparto de un retorno de infestadores entre sus campos de origen, y cierre de la recuperación de una tanda.
 *
 * El ingreso guarda en campo_origen / campania_origen_id el origen con más kg (lo que ya usan ventas y otras
 * pantallas); el detalle por campo está en cochinilla_ingreso_origenes.
 */
class CochinillaRecuperacionProceso
{
    public function __construct(private CochinillaOrigenProceso $origen)
    {
    }

    /**
     * Orígenes de la tanda de infestación de la que vuelve este ingreso, con el reparto sugerido por % de mamá y lo
     * que tiene registrado hoy.
     *
     * @return array{tanda: ?array, origenes: array<string, array{kg_madres: float, porcentaje: float, kg_sugerido: float, kg: ?float, campania_origen_id: ?int, campania: ?string}>}
     */
    public function opciones(CochinillaIngreso $ingreso): array
    {
        $tanda = $this->tandaDe($ingreso);
        if (!$tanda) {
            return ['tanda' => null, 'origenes' => []];
        }
        $sugerido = CochinillaRecuperacionReglas::repartoSugerido((float) $ingreso->total_kilos, array_map(fn($o) => (float) $o['kg'], $tanda['origenes']));
        $actual = $ingreso->origenes()->pluck('kg', 'campo_origen');
        $origenes = [];
        foreach ($tanda['origenes'] as $campo => $o) {
            $campania = $this->origen->campaniaParaOrigen($campo, $o['hasta']);
            $origenes[$campo] = [
                'kg_madres' => (float) $o['kg'],
                'porcentaje' => $sugerido[$campo]['porcentaje'],
                'kg_sugerido' => $sugerido[$campo]['kg'],
                'kg' => isset($actual[$campo]) ? (float) $actual[$campo] : null,
                'campania_origen_id' => $campania?->id,
                'campania' => $campania?->nombre_campania,
                'desde' => $o['desde'],
                'hasta' => $o['hasta'],
            ];
        }
        return ['tanda' => $tanda, 'origenes' => $origenes];
    }

    /**
     * Guarda cuántos kg del ingreso son de cada campo de origen. La suma debe ser igual a los kg del ingreso.
     *
     * @param array<string, float|string|null> $kgPorCampo
     * @param bool $manual true: lo confirmó una persona (ya no se recalcula); false: sugerencia automática
     * @param string|null $motivo obligatorio si se cambia un reparto que ya estaba confirmado (va a auditoría)
     */
    public function guardarReparto(CochinillaIngreso $ingreso, array $kgPorCampo, bool $manual = true, ?string $motivo = null): void
    {
        $yaConfirmado = $manual && $ingreso->origen_estado === 'manual' && $ingreso->origenes()->exists();
        if ($yaConfirmado && mb_strlen(trim((string) $motivo)) < 5) {
            throw new ErrorControlado('Este reparto ya estaba confirmado: indica el motivo del cambio (mínimo 5 letras).');
        }
        $antes = $ingreso->origenes()->pluck('kg', 'campo_origen')->map(fn($v) => (float) $v)->all();
        if (!CochinillaOrigenProceso::esDeInfestador($ingreso->observacion)) {
            throw new ErrorControlado('Solo los ingresos de infestadores se reparten entre campos de origen.');
        }
        $opciones = $this->opciones($ingreso);
        if (!$opciones['origenes']) {
            throw new ErrorControlado("No se encontró la infestación de {$ingreso->campo} de la que vuelven estos infestadores.");
        }
        $kg = [];
        foreach ($kgPorCampo as $campo => $valor) {
            if (!array_key_exists($campo, $opciones['origenes'])) {
                throw new ErrorControlado("{$campo} no infestó {$ingreso->campo} en esa tanda.");
            }
            $valor = round((float) str_replace(',', '', (string) $valor), 2);
            if ($valor < 0) {
                throw new ErrorControlado("Los kg de {$campo} no pueden ser negativos.");
            }
            if ($valor > 0) {
                $kg[$campo] = $valor;
            }
        }
        $total = round((float) $ingreso->total_kilos, 2);
        $suma = round(array_sum($kg), 2);
        if (abs($suma - $total) > 0.01) {
            throw new ErrorControlado('El reparto suma ' . number_format($suma, 2) . ' kg y el ingreso tiene ' . number_format($total, 2)
                . ' kg: ajusta los kilos para que sumen lo mismo.');
        }

        DB::transaction(function () use ($ingreso, $kg, $opciones, $manual, $antes, $motivo, $yaConfirmado) {
            $ingreso->origenes()->delete();
            foreach ($kg as $campo => $valor) {
                $o = $opciones['origenes'][$campo];
                CochinillaIngresoOrigen::create([
                    'cochinilla_ingreso_id' => $ingreso->id,
                    'campo_origen' => $campo,
                    'campania_origen_id' => $o['campania_origen_id'],
                    'kg' => $valor,
                    'kg_madres' => $o['kg_madres'],
                    'porcentaje_sugerido' => $o['porcentaje'],
                    'registrado_por' => $manual ? Auth::id() : null,
                ]);
            }
            arsort($kg);
            $principal = array_key_first($kg);
            $varios = count($opciones['origenes']) > 1;
            $texto = collect($kg)->map(fn($v, $c) => "{$c} " . number_format($v, 2) . ' kg')->implode(', ');
            $ingreso->forceFill([
                'campo_origen' => $principal,
                'campania_origen_id' => $opciones['origenes'][$principal]['campania_origen_id'],
                'origen_estado' => $manual ? 'manual' : ($varios ? 'sugerido' : 'infestacion'),
                'origen_detalle' => mb_substr(($manual ? 'Reparto confirmado por ' . (Auth::user()?->name ?? 'sistema') : ($varios ? 'Reparto sugerido por % de mamá' : 'Un solo origen'))
                    . ": {$texto}. Infestadores recogidos en {$ingreso->campo}.", 0, 300),
            ])->saveQuietly();

            if ($manual) {
                \App\Services\Reporte\AuditoriaServicio::registrar(CochinillaIngreso::class, $ingreso->id, 'editar',
                    ['reparto_origen' => $antes ?: null], ['reparto_origen' => $kg],
                    mb_substr(($yaConfirmado ? 'Cambio de reparto por campo de origen. Motivo: ' . trim((string) $motivo) : 'Reparto por campo de origen confirmado') . " (lote {$ingreso->lote})", 0, 255));
            }
        });
    }

    /** Reparto automático (sugerido) para ingresos que nadie confirmó. */
    public function sincronizarAutomatico(CochinillaIngreso $ingreso): void
    {
        if ($ingreso->origen_estado === 'manual') {
            return;
        }
        if (!CochinillaOrigenProceso::esDeInfestador($ingreso->observacion)) {
            $ingreso->origenes()->delete();
            return;
        }
        $opciones = $this->opciones($ingreso);
        if (!$opciones['origenes'] || (float) $ingreso->total_kilos <= 0) {
            return;
        }
        $this->guardarReparto($ingreso, array_map(fn($o) => $o['kg_sugerido'], $opciones['origenes']), false);
    }

    /** Da por recuperada una tanda (ya no se esperan más infestadores de esa infestación). */
    public function cerrar(string $campo, string $tandaInicio, ?string $nota = null): CochinillaRecuperacion
    {
        $tanda = app(CochinillaRecuperacionConsulta::class)->tanda($campo, $tandaInicio);
        if (!$tanda) {
            throw new ErrorControlado("No se encontró la infestación de {$campo} del " . Carbon::parse($tandaInicio)->format('d/m/Y') . '.');
        }
        return CochinillaRecuperacion::updateOrCreate(['campo' => $campo, 'tanda_inicio' => $tanda['inicio']], [
            'tanda_fin' => $tanda['fin'],
            'campo_campania_id' => $tanda['campania_id'],
            'kg_madres' => $tanda['kg_madres'],
            'kg_recuperados' => $tanda['kg_recuperados'],
            'nota' => $nota,
            'cerrada_por' => Auth::id(),
            'cerrada_en' => now(),
        ]);
    }

    public function reabrir(string $campo, string $tandaInicio): void
    {
        CochinillaRecuperacion::where('campo', $campo)->whereDate('tanda_inicio', $tandaInicio)->delete();
    }

    /** Tanda de infestación del campo del ingreso de la que vuelven sus infestadores (la última antes del ingreso). */
    private function tandaDe(CochinillaIngreso $ingreso): ?array
    {
        $fecha = Carbon::parse($ingreso->fecha);
        $ultima = CochinillaInfestacion::where('campo_nombre', $ingreso->campo)
            ->whereBetween('fecha', [$fecha->copy()->subDays(CochinillaOrigenProceso::DIAS_INFESTADORES)->toDateString(), $fecha->toDateString()])
            ->orderByDesc('fecha')->first();
        return $ultima ? $this->origen->tanda($ingreso->campo, $ultima) : null;
    }
}
