<?php

namespace App\Services\Planilla\Parametro;

use App\Exceptions\ErrorControlado;
use App\Models\ConfiguracionHistorial;
use App\Models\PlanDescuentoSp;
use App\Models\PlanPrimaComisionHistorico;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lectura de los parámetros de planilla: lo que rige en un mes (fuente única, sin copias), lo que falta, los
 * descuentos AFP / SNP del mes y qué meses ya usaron cada valor (candados).
 */
class PlanillaParametroConsulta
{
    /** @var array<string, array> memoria por petición: "anio-mes" => valores */
    private static array $memoria = [];

    /** Olvida lo leído (después de cambiar un parámetro). */
    public static function olvidar(): void
    {
        self::$memoria = [];
    }

    /**
     * Valores del mes (los vigentes el día 1). Un código sin valor vigente viene en null.
     *
     * @return array<string, ?float> codigo => valor (todos los del catálogo)
     */
    public static function delMes(int $mes, int $anio): array
    {
        $clave = "{$anio}-{$mes}";
        if (!isset(self::$memoria[$clave]['parametros'])) {
            $fecha = PlanillaParametroReglas::fechaDelMes($mes, $anio);
            $vigentes = ConfiguracionHistorial::whereIn('configuracion_codigo', PlanillaParametroReglas::codigos())
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->orderByDesc('fecha_inicio')->get()
                ->unique('configuracion_codigo')
                ->mapWithKeys(fn($h) => [$h->configuracion_codigo => (float) $h->valor])->all();
            self::$memoria[$clave]['parametros'] = array_map(fn($c) => $vigentes[$c] ?? null, array_combine(PlanillaParametroReglas::codigos(), PlanillaParametroReglas::codigos()));
        }
        return self::$memoria[$clave]['parametros'];
    }

    /** Comisiones y primas de cada AFP vigentes el día 1 del mes (la fila más reciente de cada AFP). */
    public static function afpDelMes(int $mes, int $anio): Collection
    {
        $clave = "{$anio}-{$mes}";
        if (!isset(self::$memoria[$clave]['afp'])) {
            $fecha = PlanillaParametroReglas::fechaDelMes($mes, $anio);
            self::$memoria[$clave]['afp'] = PlanPrimaComisionHistorico::whereDate('fecha_inicio', '<=', $fecha)
                ->orderByDesc('fecha_inicio')->get()->unique('referencia')
                ->sortBy(fn($a) => array_search($a->referencia, PlanillaParametroReglas::AFP))->values();
        }
        return self::$memoria[$clave]['afp'];
    }

    /**
     * Descuento de cada modalidad del sistema de pensiones en el mes (HAB F, HAB M… y SNP), calculado de las AFP y del
     * descuento SNP vigentes. Flujo: comisión + prima + aporte; Mixta: prima + aporte (sin comisión). Mayores de 65: sin prima.
     *
     * @return Collection<string, object{codigo:string, referencia:string, tipo:?string, comision:float, prima_seguros:float,
     *   aporte_obligatorio:float, porcentaje:float, porcentaje_65:float}>
     */
    public static function descuentosSp(int $mes, int $anio): Collection
    {
        $afp = self::afpDelMes($mes, $anio)->keyBy('referencia');
        $snp = self::delMes($mes, $anio)['descuento_snp'];
        return PlanDescuentoSp::all()->mapWithKeys(function ($d) use ($afp, $snp) {
            if ($d->codigo === 'SNP') {
                return $snp === null ? [] : ['SNP' => (object) ['codigo' => 'SNP', 'referencia' => 'SNP', 'tipo' => null, 'comision' => 0.0,
                    'prima_seguros' => 0.0, 'aporte_obligatorio' => $snp, 'porcentaje' => $snp, 'porcentaje_65' => $snp]];
            }
            $pc = $afp->get($d->referencia);
            if (!$pc) {
                return [];
            }
            $flujo = $d->tipo === 'Flujo';
            $comision = $flujo ? (float) $pc->comision_flujo : 0.0;
            return [$d->codigo => (object) [
                'codigo' => $d->codigo, 'referencia' => $d->referencia, 'tipo' => $d->tipo,
                'comision' => $comision,
                'prima_seguros' => (float) $pc->prima_seguros,
                'aporte_obligatorio' => (float) $pc->aporte_obligatorio,
                'porcentaje' => round($comision + (float) $pc->prima_seguros + (float) $pc->aporte_obligatorio, 4),
                'porcentaje_65' => round($comision + (float) $pc->aporte_obligatorio, 4),
            ]];
        });
    }

    public static function descuentoSp(int $mes, int $anio, ?string $codigo): ?object
    {
        return $codigo ? self::descuentosSp($mes, $anio)->get($codigo) : null;
    }

    /**
     * Lo que falta para calcular la planilla del mes.
     *
     * @return array{parametros: string[], afp: string[]} nombres de parámetros y AFP sin valor vigente
     */
    public static function faltantes(int $mes, int $anio, string $planilla = 'agraria'): array
    {
        $valores = self::delMes($mes, $anio);
        $parametros = array_values(array_map(fn($c) => PlanillaParametroReglas::nombre($c),
            array_filter(PlanillaParametroReglas::codigos($planilla), fn($c) => $valores[$c] === null)));
        $afp = array_values(array_diff(PlanillaParametroReglas::AFP, self::afpDelMes($mes, $anio)->pluck('referencia')->all()));
        return ['parametros' => $parametros, 'afp' => $afp];
    }

    /** Detiene la planilla si falta algún parámetro, con el detalle de qué registrar y dónde. */
    public static function exigir(int $mes, int $anio, string $planilla = 'agraria'): void
    {
        $f = self::faltantes($mes, $anio, $planilla);
        $periodo = sprintf('%02d/%04d', $mes, $anio);
        $partes = [];
        if ($f['parametros']) {
            $partes[] = 'En Planilla → Parámetros de planilla faltan valores vigentes en ' . $periodo . ': ' . implode(', ', $f['parametros'])
                . '. Agrégalos con el mes desde el que rigen.';
        }
        if ($f['afp']) {
            $partes[] = 'En Planilla → Descuentos AFP faltan las comisiones y primas vigentes en ' . $periodo . ' de: ' . implode(', ', $f['afp']) . '.';
        }
        if ($partes) {
            throw new ErrorControlado('No se puede calcular la ' . mb_strtolower(PlanillaParametroReglas::PLANILLAS[$planilla] ?? 'planilla') . ' de ' . $periodo . '. ' . implode(' ', $partes));
        }
    }

    // ------------------------------------------------------------------ candados

    /**
     * Meses con planilla generada (agraria calculada u oficina): sus parámetros ya no pueden cambiar.
     *
     * @return Collection<int, array{mes:int, anio:int, planillas: string[]}>
     */
    public static function mesesUsados(): Collection
    {
        $agraria = DB::table('plan_mensual_detalles')->whereNotNull('calculado_en')->distinct()->pluck('plan_mensual_id')->all();
        $oficina = DB::table('plan_oficina_personals')->distinct()->pluck('plan_mensual_id')->all();
        return DB::table('plan_mensuales')->whereIn('id', array_unique(array_merge($agraria, $oficina)))
            ->orderBy('anio')->orderBy('mes')->get()
            ->map(fn($m) => ['mes' => (int) $m->mes, 'anio' => (int) $m->anio, 'planillas' => array_values(array_filter([
                in_array($m->id, $agraria) ? 'agraria' : null, in_array($m->id, $oficina) ? 'oficina' : null]))]);
    }

    /**
     * Lo que usó cada mes con planilla: sus parámetros y AFP. Sirve para comparar antes y después de un cambio.
     *
     * @return array<string, array{parametros: array, afp: array}> "mm/aaaa" => valores
     */
    public static function fotoMesesUsados(): array
    {
        $foto = [];
        foreach (self::mesesUsados() as $m) {
            $foto[sprintf('%02d/%04d', $m['mes'], $m['anio'])] = [
                'parametros' => self::delMes($m['mes'], $m['anio']),
                'afp' => self::afpDelMes($m['mes'], $m['anio'])->mapWithKeys(fn($a) => [$a->referencia => [
                    'comision_flujo' => (float) $a->comision_flujo, 'comision_saldo' => (float) $a->comision_saldo, 'prima_seguros' => (float) $a->prima_seguros,
                    'aporte_obligatorio' => (float) $a->aporte_obligatorio, 'remuneracion_maxima_asegurable' => (float) $a->remuneracion_maxima_asegurable]])->all(),
            ];
        }
        return $foto;
    }

    /** Meses con planilla cuyo día 1 cae dentro de [desde, hasta] (hasta null = sin fin). @return string[] "mm/aaaa" */
    public static function usadoEn(string $desde, ?string $hasta): array
    {
        return self::mesesUsados()->filter(function ($m) use ($desde, $hasta) {
            $f = PlanillaParametroReglas::fechaDelMes($m['mes'], $m['anio']);
            return $f >= $desde && ($hasta === null || $f <= $hasta);
        })->map(fn($m) => sprintf('%02d/%04d', $m['mes'], $m['anio']))->values()->all();
    }

    // ------------------------------------------------------------------ pantalla

    /**
     * Tarjetas de la pantalla de parámetros: valor vigente este mes, desde cuándo, motivo y si ya lo usó una planilla.
     *
     * @return array<int, array{codigo:string, nombre:string, unidad:string, planillas:string[], ayuda:string, valor:?float,
     *   texto:string, desde:?string, motivo:?string, valores:int, bloqueado:bool, en_catalogo:bool}>
     */
    public static function tarjetas(): array
    {
        $hoy = now()->startOfMonth()->toDateString();
        $todos = ConfiguracionHistorial::orderBy('fecha_inicio')->get()->groupBy('configuracion_codigo');
        $usados = self::mesesUsados();
        $otros = array_diff($todos->keys()->all(), PlanillaParametroReglas::codigos());
        $tarjetas = [];
        foreach (array_merge(PlanillaParametroReglas::codigos(), $otros) as $codigo) {
            $lista = $todos->get($codigo, collect());
            $vigente = $lista->filter(fn($h) => $h->fecha_inicio->toDateString() <= $hoy)->last();
            $catalogo = PlanillaParametroReglas::CATALOGO[$codigo] ?? null;
            $tarjetas[] = [
                'codigo' => $codigo,
                'nombre' => $catalogo[0] ?? $codigo,
                'unidad' => $catalogo[1] ?? '',
                'planillas' => $catalogo[2] ?? [],
                'ayuda' => $catalogo[3] ?? 'El sistema no usa este valor.',
                'valor' => $vigente ? (float) $vigente->valor : null,
                'texto' => $vigente ? PlanillaParametroReglas::formato($codigo, $vigente->valor) : 'Sin valor',
                'desde' => $vigente?->fecha_inicio->toDateString(),
                'motivo' => $vigente?->motivo,
                'proximo' => $lista->filter(fn($h) => $h->fecha_inicio->toDateString() > $hoy)->first()?->fecha_inicio->toDateString(),
                'valores' => $lista->count(),
                'bloqueado' => $vigente && $usados->contains(fn($m) => PlanillaParametroReglas::fechaDelMes($m['mes'], $m['anio']) >= $vigente->fecha_inicio->toDateString()
                    && ($vigente->fecha_fin === null || PlanillaParametroReglas::fechaDelMes($m['mes'], $m['anio']) <= $vigente->fecha_fin->toDateString())),
                'en_catalogo' => $catalogo !== null,
            ];
        }
        return $tarjetas;
    }

    /**
     * Historial de un parámetro (más reciente primero), con los meses de planilla que usaron cada valor.
     *
     * @return array<int, array{id:int, desde:string, hasta:?string, valor:float, texto:string, motivo:?string, usuario:?string,
     *   registrado:?string, usado_en:string[]}>
     */
    public static function historial(string $codigo): array
    {
        return ConfiguracionHistorial::with('registradoPor:id,name')->where('configuracion_codigo', $codigo)
            ->orderByDesc('fecha_inicio')->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'desde' => $h->fecha_inicio->toDateString(),
                'hasta' => $h->fecha_fin?->toDateString(),
                'valor' => (float) $h->valor,
                'texto' => PlanillaParametroReglas::formato($codigo, $h->valor),
                'motivo' => $h->motivo,
                'usuario' => $h->registradoPor?->name,
                'registrado' => $h->created_at?->format('d/m/Y'),
                'usado_en' => self::usadoEn($h->fecha_inicio->toDateString(), $h->fecha_fin?->toDateString()),
            ])->all();
    }

    /** Resumen de AFP para la tarjeta: fecha de los valores vigentes este mes, y si los usó una planilla. */
    public static function tarjetaAfp(): array
    {
        $hoy = now();
        $vigentes = self::afpDelMes($hoy->month, $hoy->year);
        $desde = $vigentes->max(fn($a) => Carbon::parse($a->fecha_inicio)->toDateString());
        return [
            'desde' => $desde,
            'afp' => $vigentes->map(fn($a) => ['referencia' => $a->referencia, 'comision_flujo' => (float) $a->comision_flujo,
                'prima_seguros' => (float) $a->prima_seguros, 'aporte_obligatorio' => (float) $a->aporte_obligatorio])->all(),
            'faltan' => array_values(array_diff(PlanillaParametroReglas::AFP, $vigentes->pluck('referencia')->all())),
            'motivo' => $vigentes->first()?->motivo,
        ];
    }
}
