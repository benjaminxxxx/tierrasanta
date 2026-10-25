<?php

namespace App\Services\Planilla\Parametro;

use App\Exceptions\ErrorControlado;
use App\Exceptions\ParametroAfectaPlanillas;
use App\Models\Configuracion;
use App\Models\ConfiguracionHistorial;
use App\Models\PlanMensual;
use App\Models\PlanPrimaComisionHistorico;
use App\Services\Planilla\Oficina\PlanillaOficinaExcel;
use App\Services\Planilla\Oficina\PlanillaOficinaProceso;
use App\Services\Planilla\PlanillaServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de escritura de los parámetros de planilla (pantalla, modal de Generar planilla, Descuentos AFP y Excel
 * de recursos).
 *
 * Meses con planilla generada: un cambio que altere lo que esos meses usaron no se guarda sin confirmar. Sin
 * $confirmado se lanza ParametroAfectaPlanillas con los meses y qué cambia en cada uno (la pantalla pregunta); con
 * $confirmado se guarda y se regeneran esas planillas (agraria y/o oficina, con su Excel). Ver $afectados / $regenerados.
 *
 * fecha_fin se calcula sola: el día anterior al siguiente valor del mismo parámetro.
 */
class PlanillaParametroProceso
{
    /** Meses que cambió el último guardado (y se regeneraron) */
    public array $afectados = [];

    /** @var array<int, array{periodo:string, planilla:string, ok:bool, detalle:string}> resultado de regenerar */
    public array $regenerados = [];

    /** Nuevo valor de un parámetro desde un mes. */
    public function agregar(string $codigo, $valor, string $desde, ?string $motivo = null, bool $confirmado = false): ConfiguracionHistorial
    {
        $d = $this->validar($codigo, $valor, $desde);
        return $this->guardarConConfirmacion(function () use ($codigo, $d, $motivo) {
            if (ConfiguracionHistorial::where('configuracion_codigo', $codigo)->whereDate('fecha_inicio', $d['desde'])->exists()) {
                throw new ErrorControlado(PlanillaParametroReglas::nombre($codigo) . ' ya tiene un valor desde ' . Carbon::parse($d['desde'])->format('m/Y') . ': edítalo o elige otro mes.');
            }
            $this->asegurarCatalogo($codigo);
            $h = ConfiguracionHistorial::create(['configuracion_codigo' => $codigo, 'valor' => $d['valor'], 'fecha_inicio' => $d['desde'],
                'motivo' => $this->texto($motivo), 'registrado_por' => Auth::id()]);
            AuditoriaServicio::registrar(ConfiguracionHistorial::class, $h->id, 'crear', null, $h->only(['configuracion_codigo', 'valor', 'fecha_inicio', 'motivo']), 'Parámetro de planilla');
            $this->recalcularVigencias($codigo);
            return $h;
        }, $confirmado);
    }

    /** Corrige un valor (valor, mes desde y motivo). */
    public function editar(int $id, $valor, string $desde, ?string $motivo = null, bool $confirmado = false): ConfiguracionHistorial
    {
        $h = ConfiguracionHistorial::findOrFail($id);
        $d = $this->validar($h->configuracion_codigo, $valor, $desde);
        return $this->guardarConConfirmacion(function () use ($h, $d, $motivo) {
            if (ConfiguracionHistorial::where('configuracion_codigo', $h->configuracion_codigo)->where('id', '<>', $h->id)->whereDate('fecha_inicio', $d['desde'])->exists()) {
                throw new ErrorControlado('Ya hay otro valor desde ' . Carbon::parse($d['desde'])->format('m/Y') . '.');
            }
            $antes = $h->only(['valor', 'fecha_inicio', 'motivo']);
            $h->update(['valor' => $d['valor'], 'fecha_inicio' => $d['desde'], 'motivo' => $this->texto($motivo)]);
            AuditoriaServicio::registrar(ConfiguracionHistorial::class, $h->id, 'editar', $antes, $h->only(['valor', 'fecha_inicio', 'motivo']), 'Parámetro de planilla');
            $this->recalcularVigencias($h->configuracion_codigo);
            return $h->fresh();
        }, $confirmado);
    }

    public function eliminar(int $id, bool $confirmado = false): void
    {
        $h = ConfiguracionHistorial::findOrFail($id);
        $this->guardarConConfirmacion(function () use ($h) {
            AuditoriaServicio::registrar(ConfiguracionHistorial::class, $h->id, 'eliminar', $h->only(['configuracion_codigo', 'valor', 'fecha_inicio', 'motivo']), null, 'Parámetro de planilla');
            $h->delete();
            $this->recalcularVigencias($h->configuracion_codigo);
        }, $confirmado);
    }

    /**
     * Excel de recursos: crea o actualiza el valor de ese código y "desde". El Excel no puede preguntar: si afecta
     * planillas generadas falla (se cambia desde la pantalla, que pide confirmación y regenera).
     */
    public function guardarDesdeExcel(string $codigo, $valor, string $desde, ?string $motivo): void
    {
        $existente = ConfiguracionHistorial::where('configuracion_codigo', $codigo)->whereDate('fecha_inicio', PlanillaParametroReglas::desde($desde))->first();
        $existente ? $this->editar($existente->id, $valor, $desde, $motivo ?? $existente->motivo) : $this->agregar($codigo, $valor, $desde, $motivo);
    }

    // ------------------------------------------------------------------ AFP

    /**
     * Comisiones y primas de las AFP desde un mes (pegadas de la SBS o del Excel). Reemplaza las de ese mes.
     *
     * @param array<string, array{comision_flujo: float, comision_saldo: ?float, prima_seguros: float, aporte_obligatorio: float,
     *   remuneracion_maxima_asegurable: ?float}> $valores referencia => datos
     */
    public function guardarAfp(string $desde, array $valores, ?string $motivo = null, bool $confirmado = false): void
    {
        $fecha = PlanillaParametroReglas::desde($desde);
        $this->guardarConConfirmacion(function () use ($fecha, $valores, $motivo) {
            foreach ($valores as $referencia => $datos) {
                $h = PlanPrimaComisionHistorico::firstOrNew(['referencia' => $referencia, 'fecha_inicio' => $fecha]);
                $antes = $h->exists ? $h->only(array_keys($datos)) : null;
                $h->fill($datos + ['motivo' => $this->texto($motivo) ?? $h->motivo, 'registrado_por' => Auth::id()])->save();
                AuditoriaServicio::registrar(PlanPrimaComisionHistorico::class, $h->id, $antes ? 'editar' : 'crear', $antes, $datos, "AFP {$referencia}");
            }
        }, $confirmado);
    }

    public function eliminarAfp(string $desde, bool $confirmado = false): int
    {
        $fecha = PlanillaParametroReglas::desde($desde);
        return $this->guardarConConfirmacion(function () use ($fecha) {
            $lista = PlanPrimaComisionHistorico::whereDate('fecha_inicio', $fecha)->get();
            foreach ($lista as $h) {
                AuditoriaServicio::registrar(PlanPrimaComisionHistorico::class, $h->id, 'eliminar', $h->toArray(), null, "AFP {$h->referencia}");
                $h->delete();
            }
            return $lista->count();
        }, $confirmado);
    }

    // ------------------------------------------------------------------ meses con planilla

    /** Si el cambio no se puede guardar sin confirmar (o tiene otro error), el motivo; sin guardar nada. Para el Excel. */
    public function errorSinGuardar(callable $cambio): ?string
    {
        DB::beginTransaction();
        try {
            $cambio();
            return null;
        } catch (ParametroAfectaPlanillas $e) {
            return 'Afecta planillas ya generadas (' . implode(', ', array_column($e->meses, 'periodo')) . '): cámbialo en Planilla → Parámetros de planilla, '
                . 'que pide confirmación y regenera esas planillas.';
        } catch (ErrorControlado $e) {
            return $e->getMessage();
        } finally {
            DB::rollBack();
            PlanillaParametroConsulta::olvidar();
        }
    }

    /**
     * Ejecuta el cambio y compara lo que usa cada mes con planilla antes y después. Si algo cambia: sin confirmar se
     * deshace y se avisa (ParametroAfectaPlanillas); confirmado se guarda y se regeneran esos meses.
     */
    private function guardarConConfirmacion(callable $cambio, bool $confirmado)
    {
        $this->afectados = $this->regenerados = [];
        PlanillaParametroConsulta::olvidar();
        $antes = PlanillaParametroConsulta::fotoMesesUsados();
        $r = DB::transaction(function () use ($cambio, $antes, $confirmado) {
            $r = $cambio();
            PlanillaParametroConsulta::olvidar();
            $this->afectados = $this->comparar($antes, PlanillaParametroConsulta::fotoMesesUsados());
            if ($this->afectados && !$confirmado) {
                throw new ParametroAfectaPlanillas($this->afectados);
            }
            return $r;
        });
        PlanillaParametroConsulta::olvidar();
        if ($this->afectados) {
            $this->regenerados = $this->regenerar($this->afectados);
        }
        return $r;
    }

    /** @return array<int, array{periodo:string, mes:int, anio:int, planillas:string[], cambios:string[]}> */
    private function comparar(array $antes, array $despues): array
    {
        $usados = PlanillaParametroConsulta::mesesUsados()->keyBy(fn($m) => sprintf('%02d/%04d', $m['mes'], $m['anio']));
        $afectados = [];
        foreach ($antes as $periodo => $valores) {
            $cambios = [];
            foreach ($valores['parametros'] as $codigo => $v) {
                $nuevo = $despues[$periodo]['parametros'][$codigo] ?? null;
                if ($v !== $nuevo) {
                    $cambios[] = PlanillaParametroReglas::nombre($codigo) . ': ' . PlanillaParametroReglas::formato($codigo, $v) . ' → ' . PlanillaParametroReglas::formato($codigo, $nuevo);
                }
            }
            foreach (array_unique(array_merge(array_keys($valores['afp']), array_keys($despues[$periodo]['afp'] ?? []))) as $afp) {
                if (($valores['afp'][$afp] ?? null) != ($despues[$periodo]['afp'][$afp] ?? null)) {
                    $cambios[] = "Comisiones y primas de {$afp}";
                }
            }
            if ($cambios) {
                [$mes, $anio] = array_map('intval', explode('/', $periodo));
                $afectados[] = ['periodo' => $periodo, 'mes' => $mes, 'anio' => $anio, 'planillas' => $usados[$periodo]['planillas'] ?? [], 'cambios' => $cambios];
            }
        }
        return $afectados;
    }

    /**
     * Vuelve a generar las planillas de los meses (con sus ajustes manuales) y sus Excel. Un error en un mes no detiene
     * los demás: queda en el resultado.
     */
    public function regenerar(array $meses): array
    {
        $r = [];
        foreach ($meses as $m) {
            foreach ($m['planillas'] as $planilla) {
                try {
                    $plan = PlanMensual::where('mes', $m['mes'])->where('anio', $m['anio'])->firstOrFail();
                    if ($planilla === 'agraria') {
                        $g = app(PlanillaServicio::class)->generarProyeccion($m['mes'], $m['anio']);
                        $plan->update(['excel' => app(PlanillaServicio::class)->generarExcelPlanilla($m['mes'], $m['anio'])]);
                        $detalle = "{$g['procesados']} de {$g['total']} trabajadores";
                    } else {
                        $g = app(PlanillaOficinaProceso::class)->generar($m['mes'], $m['anio']);
                        $plan->refresh()->update(['excel_oficina' => app(PlanillaOficinaExcel::class)->generar($plan)]);
                        $detalle = "{$g['personas']} personas";
                    }
                    $r[] = ['periodo' => $m['periodo'], 'planilla' => $planilla, 'ok' => true, 'detalle' => $detalle];
                } catch (\Throwable $e) {
                    $r[] = ['periodo' => $m['periodo'], 'planilla' => $planilla, 'ok' => false, 'detalle' => $e->getMessage()];
                }
            }
        }
        return $r;
    }

    // ------------------------------------------------------------------ apoyo

    /** fecha_fin de cada valor = día anterior al siguiente; el último queda abierto. */
    private function recalcularVigencias(string $codigo): void
    {
        $lista = ConfiguracionHistorial::where('configuracion_codigo', $codigo)->orderBy('fecha_inicio')->get();
        foreach ($lista as $i => $h) {
            $siguiente = $lista->get($i + 1);
            $fin = $siguiente ? $siguiente->fecha_inicio->copy()->subDay()->toDateString() : null;
            if ($h->fecha_fin?->toDateString() !== $fin) {
                $h->update(['fecha_fin' => $fin]);
            }
        }
    }

    private function validar(string $codigo, $valor, string $desde): array
    {
        if (!is_numeric($valor) || (float) $valor < 0) {
            throw new ErrorControlado('El valor debe ser un número mayor o igual a 0.');
        }
        if (!isset(PlanillaParametroReglas::CATALOGO[$codigo]) && !Configuracion::whereKey($codigo)->exists()) {
            throw new ErrorControlado("El parámetro \"{$codigo}\" no existe.");
        }
        try {
            $fecha = PlanillaParametroReglas::desde($desde);
        } catch (\Throwable) {
            throw new ErrorControlado('Indica desde qué mes rige el valor.');
        }
        return ['valor' => round((float) $valor, 4), 'desde' => $fecha];
    }

    /** Los parámetros del catálogo existen en la tabla configuracion (la usa la llave foránea). */
    private function asegurarCatalogo(string $codigo): void
    {
        if (isset(PlanillaParametroReglas::CATALOGO[$codigo]) && !Configuracion::whereKey($codigo)->exists()) {
            Configuracion::create(['codigo' => $codigo, 'valor' => '', 'descripcion' => PlanillaParametroReglas::nombre($codigo)]);
        }
    }

    private function texto(?string $t): ?string
    {
        $t = trim((string) $t);
        return $t === '' ? null : mb_substr($t, 0, 255);
    }
}
