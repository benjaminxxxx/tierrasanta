<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;

/**
 * Tarea pendiente: infestaciones que pasaron los días de aviso (Sistema → Configuración) sin darse por recuperadas.
 * Tarea de estado: una subtarea por infestación; desaparece al darla por recuperada.
 */
class CochinillaRecuperacionDetector
{
    public const TIPO = 'cochinilla-recuperacion';

    public function __construct(private TareaPendienteServicio $registrador, private CochinillaRecuperacionConsulta $consulta)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $dias = CochinillaRecuperacionReglas::diasAlerta();
        $hijas = $this->consulta->tandas('alerta')->map(fn($t) => [
            'clave' => $t['clave'],
            'campo' => $t['campo'],
            'titulo' => "{$t['campo']}: infestación del " . \Carbon\Carbon::parse($t['inicio'])->format('d/m/Y') . " sin recuperar ({$t['dias']} días)",
            'descripcion' => number_format($t['kg_madres'], 2) . ' kg de mamá de ' . implode(', ', array_keys($t['origenes']))
                . '; recuperado ' . number_format($t['kg_recuperados'], 2) . ' kg. Si ya se recogió todo, dala por recuperada.',
        ])->values()->all();

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Cochinilla por recuperar',
            'descripcion' => count($hijas) . " infestación(es) con más de {$dias} días sin darse por recuperadas.",
            'variante' => 'warning',
            'cantidad_afectados' => count($hijas),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Ver cochinilla por recuperar', 'tipo_accion' => 'link', 'url' => route('cochinilla.recuperacion', ['estado' => 'alerta'])]],
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
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [['titulo' => 'Revisar', 'tipo_accion' => 'link', 'url' => route('cochinilla.recuperacion', ['estado' => 'todas', 'campo' => $h['campo']])]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')->whereNotNull('parent_id')
            ->whereNotIn('clave', array_column($hijas, 'clave'))->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }
}
