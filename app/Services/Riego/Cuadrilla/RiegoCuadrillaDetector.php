<?php

namespace App\Services\Riego\Cuadrilla;

use App\Models\ConsolidadoRiego;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente "Riego de cuadrilleros" (por mes): regadores cuadrilleros cuyo reporte de riego no coincide con su
 * registro de cuadrilla (ver RiegoCuadrillaConsulta). Deja la marca "sincronizado" de cada consolidado al día.
 */
class RiegoCuadrillaDetector
{
    public const TIPO = 'riego-cuadrilla';

    private const TITULOS = [
        'es_planilla' => 'es de planilla (¿trabajo extra?)',
        'sin_registro' => 'sin registro de cuadrilla',
        'labor' => 'tramos que no son FDM/81 en el horario del riego',
        'horas' => 'horas FDM/81 distintas al jornal de riego',
        'costo' => 'pagado distinto a lo que costea la BDD',
    ];

    public function __construct(private RiegoCuadrillaConsulta $consulta)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $inicio = Carbon::parse($fechaInicio ?? now()->startOfMonth())->startOfMonth()->toDateString();
        $fin = Carbon::parse($inicio)->endOfMonth()->toDateString();
        $claveMes = substr($inicio, 0, 7);
        $nombreMes = Carbon::parse($inicio)->translatedFormat('F Y');

        $filas = $this->consulta->revisar($inicio, $fin);
        foreach ($filas as $f) {
            ConsolidadoRiego::whereKey($f['consolidado_id'])->update(['sincronizado' => $f['sincronizado']]);
        }
        $conProblema = array_values(array_filter($filas, fn($f) => $f['problemas']));

        $registrador = app(TareaPendienteServicio::class);
        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO, 'clave' => $claveMes,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'titulo' => "Riego de cuadrilleros — {$nombreMes}",
            'descripcion' => 'El riego de un cuadrillero se registra en su registro de cuadrilla como FDM/81, con las mismas horas que el '
                . 'jornal de riego. El detalle por campo lo da el reporte de riego (así lo costea la BDD).',
            'variante' => collect($conProblema)->contains(fn($f) => array_diff_key($f['problemas'], ['costo' => true, 'es_planilla' => true])) ? 'danger' : 'warning',
            'cantidad_afectados' => count($conProblema),
            'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes', 'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($conProblema as $f) {
            $clave = "{$claveMes}-c{$f['cuadrillero_id']}-{$f['fecha']}";
            $vigentes[] = $clave;
            $soloCosto = !array_diff_key($f['problemas'], ['costo' => true, 'es_planilla' => true]);
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO, 'clave' => $clave, 'parent_id' => $padre?->id,
                'fecha_inicio' => $f['fecha'], 'fecha_fin' => $f['fecha'],
                'titulo' => "{$f['nombre']} — " . Carbon::parse($f['fecha'])->format('d/m') . ': '
                    . implode(', ', array_map(fn($k) => self::TITULOS[$k], array_keys($f['problemas']))),
                'descripcion' => implode(' ', $f['problemas']),
                'variante' => $soloCosto ? 'warning' : 'danger',
                'cantidad_afectados' => 1,
                'servicio' => self::class, 'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [
                    ['titulo' => 'Registro de cuadrilla', 'tipo_accion' => 'link', 'url' => route('cuadrilla.registro_diario', ['fecha' => $f['fecha']])],
                    ['titulo' => 'Reporte de riego', 'tipo_accion' => 'link', 'url' => route('riego.reporte_diario', ['fecha' => $f['fecha']])],
                ],
            ]);
        }

        // Los que ya se corrigieron se cierran
        TareaPendiente::where('tipo', self::TIPO)->where('estado', 'pendiente')
            ->where('clave', 'like', "{$claveMes}-c%")->whereNotIn('clave', $vigentes)->get()
            ->each(fn($t) => $registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $t->clave, 'cantidad_afectados' => 0]));
    }
}
