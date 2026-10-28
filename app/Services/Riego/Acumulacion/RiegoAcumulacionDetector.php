<?php

namespace App\Services\Riego\Acumulacion;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente "Horas acumuladas de riego fuera de mes": lo acumulado en el mes debe usarse en el mismo mes
 * (la BDD costea las horas reales el día que se regó; si se usan otro mes, el cierre de costos no cuadra con lo pagado).
 * Si se ve que un regador va a exceder, lo nuevo se registra como cuadrillero.
 */
class RiegoAcumulacionDetector
{
    public const TIPO = 'riego-acumuladas-fuera-de-mes';

    public function __construct(private RiegoAcumulacionConsulta $consulta)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $inicio = Carbon::parse($fechaInicio ?? now()->startOfMonth())->startOfMonth()->toDateString();
        $fin = Carbon::parse($inicio)->endOfMonth()->toDateString();
        $claveMes = substr($inicio, 0, 7);
        $nombreMes = Carbon::parse($inicio)->translatedFormat('F Y');
        $mesEnCurso = now()->toDateString() <= $fin;
        $casos = $this->consulta->fueraDeMes($inicio, $fin);
        $h = fn(int $m) => intdiv($m, 60) . 'h ' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT) . 'min';

        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Horas acumuladas de riego — {$nombreMes}",
            'descripcion' => 'Lo acumulado en el mes se usa en el mismo mes: la BDD ya costeó esas horas el día que se regó y el cierre de costos '
                . 'debe cuadrar con lo pagado. Si un regador va a exceder, registra lo nuevo como cuadrillero.',
            'variante' => $mesEnCurso ? 'warning' : 'danger',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($casos as $c) {
            $clave = "{$claveMes}-" . class_basename($c['trabajador_type']) . "-{$c['trabajador_id']}";
            $vigentes[] = $clave;
            $partes = array_filter([
                $c['sin_usar'] ? ($mesEnCurso ? 'quedan ' : 'quedaron sin usar ') . $h($c['sin_usar']) : null,
                $c['usado_otro_mes'] ? $h($c['usado_otro_mes']) . ' usadas en otro mes' : null,
            ]);
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'titulo' => "{$c['nombre']}: " . implode(' · ', $partes),
                'descripcion' => 'Acumuladas los días ' . implode(', ', $c['dias']) . '. '
                    . ($mesEnCurso ? 'Úsalas antes de fin de mes (botón "Usar … acumuladas" en el reporte de riego).' : 'Revisa el cierre de costos de ese mes.'),
                'variante' => $mesEnCurso && !$c['usado_otro_mes'] ? 'warning' : 'danger',
                'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Reporte de riego', 'tipo_accion' => 'link', 'url' => route('riego.reporte_diario')]],
            ]);
        }
        // Usó más horas acumuladas de las que tenía ese día
        foreach ($this->consulta->usosSinSaldo($inicio, $fin) as $u) {
            $clave = "{$claveMes}-exceso-" . class_basename($u['trabajador_type']) . "-{$u['trabajador_id']}-{$u['fecha']}";
            $vigentes[] = $clave;
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $u['fecha'], 'fecha_fin' => $u['fecha'],
                'titulo' => "{$u['nombre']} — " . Carbon::parse($u['fecha'])->format('d/m') . ': usó ' . $h($u['usado'])
                    . ' acumuladas y solo tenía ' . $h($u['cubierto']),
                'descripcion' => 'Las ' . $h($u['usado'] - $u['cubierto']) . ' de más no salen de ningún día acumulado: se pagaron sin respaldo en el banco de horas. '
                    . 'Corrige el uso en el reporte de riego de ese día (o regístralo como día trabajado / falta).',
                'variante' => 'danger', 'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Reporte de riego', 'tipo_accion' => 'link', 'url' => route('riego.reporte_diario', ['fecha' => $u['fecha']])]],
            ]);
        }
        $registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $claveMes,
            'cantidad_afectados' => count($vigentes)]);

        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->where('clave', 'like', "{$claveMes}-%")
            ->whereNotIn('clave', $vigentes)->get()
            ->each(fn($t) => $registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $t->clave, 'cantidad_afectados' => 0]));
    }
}
