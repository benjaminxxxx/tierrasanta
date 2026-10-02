<?php

namespace App\Services\Caja\Cierre;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente: meses de caja ya terminados que aún no se cierran. Tarea de estado (sin periodo):
 * una subtarea por mes; desaparece al cerrarlo.
 */
class CajaCierreDetector
{
    public const TIPO = 'caja-cierre-mensual';

    public function __construct(private TareaPendienteServicio $registrador, private CajaCierreConsulta $cierres)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $pendientes = $this->cierres->mesesSinCerrar();
        $nombre = fn($p) => Carbon::create($p['anio'], $p['mes'], 1)->locale('es')->translatedFormat('F Y');

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Caja sin cerrar',
            'descripcion' => count($pendientes) . ' mes(es) de caja terminados sin cerrar'
                . ($pendientes ? ': ' . implode(', ', array_map($nombre, $pendientes)) . '.' : '.')
                . ' Cerrado el mes, sus movimientos ya no se pueden modificar.',
            'variante' => 'warning',
            'cantidad_afectados' => count($pendientes),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ir a caja', 'tipo_accion' => 'link', 'url' => route('caja.movimientos')]],
        ]);

        $vigentes = [];
        foreach ($pendientes as $p) {
            $clave = sprintf('%04d-%02d', $p['anio'], $p['mes']);
            $vigentes[] = $clave;
            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => Carbon::create($p['anio'], $p['mes'], 1)->toDateString(),
                'fecha_fin' => Carbon::create($p['anio'], $p['mes'], 1)->endOfMonth()->toDateString(),
                'titulo' => 'Falta cerrar la caja de ' . $nombre($p),
                'descripcion' => 'Revisa los movimientos y el arqueo del mes, y ciérralo.',
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Abrir el mes',
                    'tipo_accion' => 'link',
                    'url' => route('caja.movimientos', ['anio' => $p['anio'], 'mes' => $p['mes']]),
                ]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->whereNotIn('clave', $vigentes)->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0,
            ]));
    }
}
