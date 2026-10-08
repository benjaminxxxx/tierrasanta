<?php

namespace App\Services\Campania\Etapa;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;

/**
 * Tarea pendiente: campañas infestadas a las que ya les tocó una evaluación de infestación (días configurados en
 * Sistema → Configuración) y no la tienen registrada. Las campañas cerradas no se avisan, salvo que se configure.
 *
 * Tarea de estado (sin periodo): una subtarea por campaña; desaparece al registrar la evaluación.
 */
class CampaniaEvaluacionInfestacionDetector
{
    public const TIPO = 'campania-evaluacion-infestacion';

    public function __construct(private TareaPendienteServicio $registrador, private CampaniaEvaluacionInfestacionConsulta $consulta)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $hijas = $this->consulta->pendientes()->map(function ($p) {
            $c = $p['campania'];
            $numeros = array_map(fn($e) => $e['numero'] . 'ª', $p['faltantes']);
            $detalle = array_map(fn($e) => $e['numero'] . 'ª al día ' . $e['dias'] . ' (' . $e['fecha']->format('d/m/Y') . ')', $p['faltantes']);
            return [
                'clave' => (string) $c->id,
                'titulo' => "{$c->campo} / {$c->nombre_campania}: falta la evaluación de infestación " . CampaniaEtapaReglas::textoDias($numeros),
                'descripcion' => 'Infestación del ' . $p['base']->format('d/m/Y') . " (van {$p['transcurridos']} días). Sin registrar: "
                    . implode(', ', $detalle) . ($c->fecha_fin ? '. La campaña está cerrada.' : '.'),
                'campania_id' => $c->id,
            ];
        })->all();

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Evaluaciones de infestación por registrar',
            'descripcion' => count($hijas) . ' campaña(s) ya pasaron los días de una evaluación de infestación ('
                . CampaniaEtapaReglas::textoDias(CampaniaEtapaReglas::diasEvaluacionInfestacion()) . ' días) y no la tienen registrada.',
            'variante' => 'info',
            'cantidad_afectados' => count($hijas),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ir a evaluación de infestación', 'tipo_accion' => 'link', 'url' => route('evaluacion.infestacion_cosecha')]],
        ]);

        foreach ($hijas as $h) {
            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
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
                'acciones' => [['titulo' => 'Registrar evaluación', 'tipo_accion' => 'link',
                    'url' => route('evaluacion.infestacion_cosecha', ['campania' => $h['campania_id']])]],
            ]);
        }

        // Las que ya se resolvieron (se registró la evaluación, se cerró la campaña, pasó el plazo…) se cierran
        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->whereNotIn('clave', array_column($hijas, 'clave'))->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }
}
