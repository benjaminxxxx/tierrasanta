<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanGrupo;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Services\Handsontable\HSTPlanillaAsistencia;
use Illuminate\Support\Carbon;

class PlanillaAsistenciaServicio
{
    /**
     * Retorna un mapa [plan_empleado_id => [dia => ['horas', 'tipo', 'color', 'descripcion']]]
     * para el mes/año indicado.
     */
    public function obtenerMapaAsistenciaMensual(int $mes, int $anio): array
    {
        $planMensual = PlanMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (!$planMensual) {
            return [];
        }

        $detalles = PlanMensualDetalle::where('plan_mensual_id', $planMensual->id)
            ->with('registrosDiarios')
            ->get();

        $catalogoAsistencia = $this->obtenerCatalogoAsistencia();

        $mapa = [];

        foreach ($detalles as $detalle) {
            $porDia = [];

            foreach ($detalle->registrosDiarios as $registro) {
                $dia = Carbon::parse($registro->fecha)->day;
                $codigo = $registro->asistencia;
                $info = $catalogoAsistencia[$codigo] ?? null;
                
                $porDia[$dia] = [
                    'horas' => $registro->total_horas !== null ? (float) $registro->total_horas : null,
                    'tipo' => $codigo,
                    'costo_dia' => (float)$registro->costo_dia,
                    'color' => $info['color'] ?? null,
                    'descripcion' => $info['descripcion'] ?? null,
                ];
            }

            // plan_empleado_id es la llave que usaremos para cruzar con PlanEmpleado
            $mapa[$detalle->plan_empleado_id] = [
                'plan_men_detalle_id' => $detalle->id,
                'dias' => $porDia,
                'total_horas' => $detalle->registrosDiarios->sum('total_horas'),
                'sueldo_real_liquidado' => (float)$detalle->sueldo_real_liquidado,
            ];
        }

        return $mapa;
    }
    /**
     * TODO: reemplazar por la fuente real (modelo/config) de tipos de asistencia.
     * Debe retornar [codigo => ['color' => '#hex', 'descripcion' => '...']]
     */
    protected function obtenerCatalogoAsistencia(): array
    {
        return [
            'P'  => ['color' => '#FDE68A', 'descripcion' => 'Permiso'],
            'DM' => ['color' => '#FCA5A5', 'descripcion' => 'Descanso médico'],
            'V'  => ['color' => '#93C5FD', 'descripcion' => 'Vacaciones'],
        ];
    }

    /**
     * Códigos de asistencia que marcan filtros rápidos (permiso, descanso médico, vacaciones).
     * TODO: ajustar a los códigos reales una vez confirmados.
     */
    public function obtenerCodigosFiltros(): array
    {
        return [
            'permiso' => ['P'],
            'descanso_medico' => ['DM'],
            'vacaciones' => ['V'],
        ];
    }
    public function obtenerHorasCompleto($mes, $anio)
    {
        // Obtener empleados con sus horas diarias

        $empleados = app(HSTPlanillaAsistencia::class)->obtenerAsistenciaMensualAgraria($mes, $anio);

        // Obtener información adicional de asistencia
        $informacionAsistenciaAdicional = app(HSTPlanillaAsistencia::class)->obtenerInformacionAsistenciaAdicional($mes, $anio);

        // Obtener colores de grupos
        $grupoColores = PlanGrupo::get()->pluck("color", "codigo")->toArray();

        // Enriquecer los datos de empleados con el color del grupo
        $empleadosEnriquecidos = collect($empleados)->map(function ($empleado, $indice) use ($grupoColores) {
            $grupoColor = isset($empleado['grupo']) && isset($grupoColores[$empleado['grupo']])
                ? $grupoColores[$empleado['grupo']]
                : '#ffffff';

            return array_merge([
                'orden' => $indice + 1,
                'empleado_grupo_color' => $grupoColor,
            ], $empleado);
        })->toArray();

        return [
            'empleados' => $empleadosEnriquecidos,
            'informacionAsistenciaAdicional' => $informacionAsistenciaAdicional
        ];
    }
}