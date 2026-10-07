<?php

namespace App\Services\Campania\Etapa;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tareas pendientes del ciclo de la campaña (sugerencias, no reglas):
 * - Campañas abiertas sin infestación que ya pasaron los días de su siguiente evaluación de brotes y no la tienen
 *   (días configurados en Sistema → Configuración).
 * - Campañas con fecha de infestación y ninguna evaluación de brotes: las evaluaciones ya debieron hacerse;
 *   si existen, hay que registrarlas.
 *
 * Tareas de estado (sin periodo): una subtarea por campaña; desaparecen al registrar la evaluación.
 */
class CampaniaEtapaDetector
{
    public const TIPO_EVALUAR = 'campania-evaluacion-brotes';
    public const TIPO_INFESTADA = 'campania-infestada-sin-brotes';

    public function __construct(private TareaPendienteServicio $registrador, private CampaniaEtapaConsulta $consulta)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $pendientes = $this->consulta->pendientes();

        $this->registrar(self::TIPO_EVALUAR, 'Campañas por evaluar (brotes)',
            fn($n) => "{$n} campaña(s) ya pasaron los días de su siguiente evaluación de brotes y no la tienen registrada. Es una sugerencia según los días configurados.",
            $pendientes['por_evaluar']->map(function ($p) {
                $c = $p['campania'];
                $s = $p['siguiente'];
                return [
                    'clave' => (string) $c->id,
                    'titulo' => "{$c->campo} / {$c->nombre_campania}: toca la {$s['numero']}ª evaluación de brotes",
                    'descripcion' => "Desde el inicio de campaña (" . Carbon::parse($c->fecha_inicio)->format('d/m/Y') . ") van "
                        . (int) Carbon::parse($c->fecha_inicio)->diffInDays(now()) . " días; la {$s['numero']}ª se sugiere al día {$s['dias']}. "
                        . "Tiene {$p['registradas']} evaluación(es)" . ($p['faltan'] > 1 ? " y le faltan {$p['faltan']}." : '.'),
                    'campania_id' => $c->id,
                ];
            })->all());

        $this->registrar(self::TIPO_INFESTADA, 'Campañas infestadas sin evaluación de brotes',
            fn($n) => "{$n} campaña(s) ya tienen fecha de infestación pero ninguna evaluación de brotes. Si se hicieron, regístralas.",
            $pendientes['infestadas_sin_evaluar']->map(fn($c) => [
                'clave' => (string) $c->id,
                'titulo' => "{$c->campo} / {$c->nombre_campania}: infestada sin evaluación de brotes",
                'descripcion' => 'Infestación del ' . Carbon::parse($c->infestacion_fecha)->format('d/m/Y')
                    . ': las evaluaciones de brotes se hacen antes. Registra las que se hicieron.',
                'campania_id' => $c->id,
            ])->all());
    }

    /** @param array<int, array{clave:string, titulo:string, descripcion:string, campania_id:int}> $hijas */
    private function registrar(string $tipo, string $titulo, \Closure $descripcion, array $hijas): void
    {
        $padre = $this->registrador->registrarOActualizar([
            'tipo' => $tipo,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => $titulo,
            'descripcion' => $descripcion(count($hijas)),
            'variante' => 'info',
            'cantidad_afectados' => count($hijas),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ir a evaluaciones de brotes', 'tipo_accion' => 'link', 'url' => route('evaluacion.brotes')]],
        ]);

        foreach ($hijas as $h) {
            $this->registrador->registrarOActualizar([
                'tipo' => $tipo,
                'clave' => $h['clave'],
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => $h['titulo'],
                'descripcion' => $h['descripcion'],
                'variante' => 'info',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Abrir la campaña', 'tipo_accion' => 'link', 'url' => route('campania.por_campo', ['campania' => $h['campania_id']])]],
            ]);
        }

        // Las que ya se resolvieron (se registró la evaluación, se infestó, se cerró la campaña…) se cierran
        TareaPendiente::where('tipo', $tipo)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->whereNotIn('clave', array_column($hijas, 'clave'))->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar(['tipo' => $tipo, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }
}
