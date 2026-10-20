<?php

namespace App\Services\Almacen\Kardex;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tarea pendiente: kardex con compras o salidas que todavía no procesó.
 *
 * Registrar una compra o una salida solo crea su movimiento de stock; el kardex (que recalcula de inicio a fin para
 * sacar el costo unitario) no se regenera en ese momento porque es pesado. Aquí se avisa qué kardex del año tienen
 * movimientos nuevos, modificados o eliminados después de su última generación (misma regla que
 * KardexActualizacionServicio::motivoDesactualizado, pero en una sola consulta para todos).
 */
class AlmacenKardexPendienteDetector
{
    public const TIPO = 'kardex-pendiente';

    public function __construct(private TareaPendienteServicio $registrador)
    {
    }

    /** @return array<int, array{id:int, producto:string, tipo:string, anio:int, motivo:string}> */
    public function pendientes(int $anio): array
    {
        $stock = DB::table('movimientos_stock')
            ->whereYear('fecha_movimiento', $anio)->where('cantidad', '>', 0)
            ->groupBy('producto_id', 'tipo_kardex')
            ->selectRaw('producto_id, tipo_kardex, COUNT(*) AS n, MAX(updated_at) AS ultimo')
            ->get()->keyBy(fn($m) => "{$m->producto_id}|{$m->tipo_kardex}");
        $procesados = DB::table('ins_kardex_movimientos')->whereNotNull('stock_movimiento_id')
            ->groupBy('kardex_id')->selectRaw('kardex_id, COUNT(*) AS n')->pluck('n', 'kardex_id');

        return DB::table('ins_kardexes as k')->leftJoin('productos as p', 'p.id', '=', 'k.producto_id')
            ->where('k.anio', $anio)
            ->get(['k.id', 'k.producto_id', 'k.tipo', 'k.anio', 'k.movimientos_actualizados_at', 'k.updated_at', 'p.nombre_comercial'])
            ->map(function ($k) use ($stock, $procesados) {
                $m = $stock->get("{$k->producto_id}|{$k->tipo}");
                $motivo = match (true) {
                    !$k->movimientos_actualizados_at => 'nunca se generaron sus movimientos',
                    $k->updated_at > $k->movimientos_actualizados_at => 'el kardex se modificó después de generarse',
                    $m && $m->ultimo >= $k->movimientos_actualizados_at => 'hay compras o salidas registradas o modificadas después de generarse',
                    ($m->n ?? 0) !== (int) ($procesados[$k->id] ?? 0) => 'hay compras o salidas agregadas o eliminadas que no procesó',
                    default => null,
                };
                return $motivo ? ['id' => $k->id, 'producto' => $k->nombre_comercial ?? "Producto #{$k->producto_id}",
                    'tipo' => $k->tipo, 'anio' => (int) $k->anio, 'motivo' => $motivo] : null;
            })->filter()->values()->all();
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $anio = $fechaInicio ? Carbon::parse($fechaInicio)->year : now()->year;
        $pendientes = $this->pendientes($anio);

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => (string) $anio,
            'fecha_inicio' => "{$anio}-01-01",
            'fecha_fin' => "{$anio}-12-31",
            'titulo' => "Kardex por regenerar {$anio}",
            'descripcion' => count($pendientes) . ' kardex con compras o salidas que todavía no procesó (su costo unitario puede estar desactualizado).',
            'variante' => 'warning',
            'cantidad_afectados' => count($pendientes),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ir al kardex', 'tipo_accion' => 'link', 'url' => route('almacen.kardex')]],
        ]);

        foreach ($pendientes as $p) {
            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => "{$anio}-{$p['id']}",
                'parent_id' => $padre?->id,
                'fecha_inicio' => "{$anio}-01-01",
                'fecha_fin' => "{$anio}-12-31",
                'titulo' => "Regenerar kardex {$p['tipo']} — {$p['producto']}",
                'descripcion' => ucfirst($p['motivo']) . '.',
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Abrir kardex', 'tipo_accion' => 'link', 'url' => route('almacen.kardex.detalle', ['insumoKardexId' => $p['id']])]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->where('clave', 'like', "{$anio}-%")
            ->whereNotIn('clave', array_map(fn($p) => "{$anio}-{$p['id']}", $pendientes))->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }
}
