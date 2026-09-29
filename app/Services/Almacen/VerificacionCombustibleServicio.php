<?php

namespace App\Services\Almacen;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tareas pendientes de salidas de combustible (por mes):
 * - combustible-sin-distribuir: salidas de máquinas que distribuyen su trabajo por campo y aún no
 *   tienen distribución. Las máquinas que no distribuyen (motos → FDM) no se revisan.
 * - combustible-distinto: salidas de un combustible distinto al configurado para la máquina
 *   (ej. petróleo a una moto a gasolina): casi siempre un error de registro.
 */
class VerificacionCombustibleServicio
{
    public const TIPO_SIN_DISTRIBUIR = 'combustible-sin-distribuir';
    public const TIPO_DISTINTO = 'combustible-distinto';

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $inicio = Carbon::parse($fechaInicio ?? now()->startOfMonth())->startOfMonth()->toDateString();
        $fin = Carbon::parse($inicio)->endOfMonth()->toDateString();
        $claveMes = substr($inicio, 0, 7);
        $nombreMes = Carbon::parse($inicio)->translatedFormat('F Y');

        $salidas = DB::table('almacen_producto_salidas as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->join('maquinarias as m', 'm.id', '=', 's.maquinaria_id')
            ->leftJoin('productos as pm', 'pm.id', '=', 'm.combustible_producto_id')
            ->where('p.categoria_codigo', 'combustible')
            ->whereBetween('s.fecha_reporte', [$inicio, $fin])
            ->selectRaw('s.id, s.fecha_reporte, s.cantidad, s.producto_id, p.nombre_comercial as combustible,
                m.id as maquinaria_id, m.nombre as maquinaria, m.usa_distribucion, m.combustible_producto_id,
                pm.nombre_comercial as combustible_maquina,
                EXISTS(SELECT 1 FROM distribucion_combustibles d WHERE d.almacen_producto_salida_id = s.id) as distribuida')
            ->orderBy('s.fecha_reporte')
            ->get();

        $sinDistribuir = $salidas->filter(fn($s) => $s->usa_distribucion && !$s->distribuida)->groupBy('maquinaria_id');
        $distinto = $salidas->filter(fn($s) => $s->combustible_producto_id && (int) $s->producto_id !== (int) $s->combustible_producto_id)
            ->groupBy('maquinaria_id');

        $this->registrar(
            self::TIPO_SIN_DISTRIBUIR, $claveMes, $inicio, $fin, $sinDistribuir,
            "Salidas de combustible sin distribuir — {$nombreMes}",
            'Salidas de máquinas que distribuyen su trabajo por campo y aún no tienen distribución de combustible.',
            'warning',
            fn($grupo) => "{$grupo->first()->maquinaria}: {$grupo->count()} salida(s), "
                . $this->cantidad($grupo) . ' ' . $grupo->first()->combustible . ' — ' . $this->fechas($grupo),
            'Distribuir'
        );

        $this->registrar(
            self::TIPO_DISTINTO, $claveMes, $inicio, $fin, $distinto,
            "Combustible distinto al de la máquina — {$nombreMes}",
            'Salidas de un combustible que no es el configurado para la máquina. Corrige la salida o la ficha de la máquina.',
            'danger',
            fn($grupo) => "{$grupo->first()->maquinaria} usa {$grupo->first()->combustible_maquina} pero recibió "
                . "{$grupo->first()->combustible}: {$grupo->count()} salida(s) — " . $this->fechas($grupo),
            'Ver salidas'
        );
    }

    private function registrar(string $tipo, string $claveMes, string $inicio, string $fin, $grupos, string $titulo,
        string $descripcion, string $variante, callable $tituloSub, string $textoAccion): void
    {
        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => $tipo, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => $titulo, 'descripcion' => $descripcion, 'variante' => $variante,
            'cantidad_afectados' => $grupos->sum(fn($g) => $g->count()),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($grupos as $maquinariaId => $grupo) {
            $clave = "{$claveMes}-maq{$maquinariaId}";
            $vigentes[] = $clave;
            $registrador->registrarOActualizar([
                'tipo' => $tipo, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => $tituloSub($grupo), 'descripcion' => null, 'variante' => $variante,
                'cantidad_afectados' => $grupo->count(),
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => $textoAccion, 'tipo_accion' => 'link', 'url' => route('almacen.salida_combustible')]],
            ]);
        }

        TareaPendiente::where('tipo', $tipo)->where('estado', 'pendiente')
            ->where('clave', 'like', "{$claveMes}-maq%")->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($t) => $registrador->registrarOActualizar(['tipo' => $tipo, 'clave' => $t->clave, 'cantidad_afectados' => 0]));
    }

    private function cantidad($grupo): string
    {
        return rtrim(rtrim(number_format((float) $grupo->sum('cantidad'), 2, '.', ''), '0'), '.');
    }

    private function fechas($grupo): string
    {
        $dias = $grupo->map(fn($s) => Carbon::parse($s->fecha_reporte)->format('d/m'))->unique()->values();
        return $dias->count() > 8 ? $dias->take(8)->implode(', ') . '…' : $dias->implode(', ');
    }
}
