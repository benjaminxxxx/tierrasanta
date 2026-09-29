<?php

namespace App\Services\Almacen\Kardex;

use App\Models\AlmacenProductoSalida;
use App\Models\InsKardex;
use App\Models\Producto;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

class VerificacionKardexServicio
{
    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        // El kardex es anual: se revisa el año del periodo elegido (por defecto el actual)
        $anio = $fechaInicio ? Carbon::parse($fechaInicio)->year : now()->year;
        $inicio = Carbon::create($anio, 1, 1)->startOfDay();
        $fin = Carbon::create($anio, 12, 31)->endOfDay();
        $periodo = ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString()];

        $combosConSalida = AlmacenProductoSalida::whereBetween('fecha_reporte', [$inicio, $fin])
            ->whereNotNull('tipo_kardex')
            ->select('producto_id', 'tipo_kardex')
            ->distinct()
            ->get();

        $kardexExistentes = InsKardex::where('anio', $anio)
            ->get(['producto_id', 'tipo'])
            ->map(fn ($k) => "{$k->producto_id}-{$k->tipo}")
            ->flip(); // set de lookup O(1)

        $faltantes = $combosConSalida->reject(
            fn ($c) => $kardexExistentes->has("{$c->producto_id}-{$c->tipo_kardex}")
        )->values();

        $registrador = app(TareaPendienteServicio::class);

        $tareaPadre = $registrador->registrarOActualizar([
            'tipo' => 'kardex-faltante',
            'clave' => (string) $anio,
            ...$periodo,
            'titulo' => "Kardex faltante {$anio}",
            'descripcion' => 'Productos con salidas este año sin kardex creado (blanco/negro).',
            'variante' => 'danger',
            'cantidad_afectados' => $faltantes->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [],
        ]);

        $nombres = Producto::whereIn('id', $faltantes->pluck('producto_id'))->pluck('nombre_comercial', 'id');
        $clavesVigentes = [];

        foreach ($faltantes as $combo) {
            $clave = "{$anio}-{$combo->producto_id}-{$combo->tipo_kardex}";
            $clavesVigentes[] = $clave;
            $nombreProducto = $nombres[$combo->producto_id] ?? "Producto #{$combo->producto_id}";

            $registrador->registrarOActualizar([
                'tipo' => 'kardex-faltante',
                'clave' => $clave,
                ...$periodo,
                'parent_id' => $tareaPadre?->id,
                'titulo' => "Falta kardex {$combo->tipo_kardex} — {$nombreProducto}",
                'descripcion' => "Tiene salidas en {$anio} sin kardex {$combo->tipo_kardex} creado.",
                'variante' => 'danger',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Crear kardex',
                    'tipo_accion' => 'link',
                    // Abre el formulario de kardex con producto, tipo y año ya elegidos
                    'url' => route('almacen.kardex', [
                        'crear' => 1,
                        'producto_id' => $combo->producto_id,
                        'tipo' => $combo->tipo_kardex,
                        'anio' => $anio,
                    ]),
                ]],
            ]);
        }

        // Cierra subtareas de años anteriores... digo, de combos que ya no faltan
        // (se creó el kardex manualmente y ya no aparece en $faltantes)
        TareaPendiente::where('tipo', 'kardex-faltante')
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->where('clave', 'like', "{$anio}-%")
            ->whereNotIn('clave', $clavesVigentes)
            ->get()
            ->each(fn ($vieja) => $registrador->registrarOActualizar([
                'tipo' => 'kardex-faltante',
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }
}