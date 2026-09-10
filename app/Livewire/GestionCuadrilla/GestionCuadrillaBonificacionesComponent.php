<?php

namespace App\Livewire\GestionCuadrilla;

use App\Livewire\Traits\ConFechaReporteDia;
use App\Models\Actividad;
use App\Models\CuadRegistroDiario;
use App\Models\PlanRegistroDiario;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaBonificacionesComponent extends Component
{
    use ConFechaReporteDia;
    use LivewireAlert;

    public $mostrarInconsistencias = false;
    public $inconsistencias = [];
    public $seleccionTodos = false;
    public $fecha1;
    public $actividades = [];
    public $actividadSeleccionada;
    public $filaExpandida = null; // Almacena el ID/Índice de la fila abierta

    public function mount()
    {
        $this->inicializarFecha();
        $this->obtenerActividades();
    }

    protected function despuesFechaModificada(string $fecha)
    {
        $this->obtenerActividades();
    }

    public function obtenerActividades()
    {
        if (!$this->fecha) {
            return;
        }
        $this->reset(['actividadSeleccionada']);
        $this->actividades = Actividad::where('fecha', $this->fecha)
        ->orderBy('campo','asc')
        ->orderBy('codigo_labor','asc')
        ->get();
    }

    public function toggleExpander($key)
    {
        $this->filaExpandida = ($this->filaExpandida === $key) ? null : $key;
    }

    public function buscarInconsistencias()
    {
        $this->mostrarInconsistencias = true;
        $this->seleccionTodos = false;
        $this->filaExpandida = null;

        // 1. CUADRILLA (Consulta con WHERE EXISTS para prevenir multiplicidad de horas)
        $cuadrilla = DB::table('cuad_registros_diarios as rd')
            ->leftJoin(
                DB::raw('(
                    SELECT 
                        ba.registro_diario_id, 
                        SUM(ba.total_bono) as suma_bono 
                    FROM cuad_bonos_actividades ba
                    INNER JOIN actividades a ON a.id = ba.actividad_id
                    WHERE EXISTS (
                        SELECT 1 
                        FROM cuad_detalles_horas dh 
                        WHERE dh.registro_diario_id = ba.registro_diario_id 
                        AND dh.codigo_labor = a.codigo_labor
                    )
                    GROUP BY ba.registro_diario_id
                ) as sb'),
                'sb.registro_diario_id',
                '=',
                'rd.id'
            )
            ->join('cuad_cuadrilleros as c', 'c.id', '=', 'rd.cuadrillero_id')
            ->select(
                'rd.id as registro_diario_id',
                'rd.fecha',
                'rd.total_bono as total_actual',
                DB::raw('COALESCE(sb.suma_bono, 0) as total_correcto'),
                'c.nombres as nombre'
            )
            ->whereRaw('ABS(COALESCE(rd.total_bono, 0) - COALESCE(sb.suma_bono, 0)) > 0.01')
            ->get()
            ->map(fn($r) => $this->mapearFila('CUADRILLA', $r));

        // 2. PLANILLA
        $planilla = DB::table('plan_registros_diarios as rd')
            ->leftJoin(
                DB::raw('(
                    SELECT 
                        pba.registro_diario_id, 
                        SUM(pba.total_bono) as suma_bono 
                    FROM plan_actividad_bonos pba
                    INNER JOIN actividades a ON a.id = pba.actividad_id
                    WHERE EXISTS (
                        SELECT 1 
                        FROM plan_detalles_horas dh 
                        WHERE dh.plan_reg_dia_id = pba.registro_diario_id 
                        AND dh.codigo_labor = a.codigo_labor
                    )
                    GROUP BY pba.registro_diario_id
                ) as sb'),
                'sb.registro_diario_id',
                '=',
                'rd.id'
            )
            ->join('plan_mensual_detalles as pmd', 'pmd.id', '=', 'rd.plan_det_men_id')
            ->join('plan_empleados as e', 'e.id', '=', 'pmd.plan_empleado_id')
            ->select(
                'rd.id as registro_diario_id',
                'rd.fecha',
                'rd.total_bono as total_actual',
                DB::raw('COALESCE(sb.suma_bono, 0) as total_correcto'),
                'pmd.nombres as nombre'
            )
            ->whereRaw('ABS(COALESCE(rd.total_bono, 0) - COALESCE(sb.suma_bono, 0)) > 0.01')
            ->get()
            ->map(fn($r) => $this->mapearFila('PLANILLA', $r));

        $this->inconsistencias = $cuadrilla->concat($planilla)
            ->sortByDesc(fn($i) => abs($i['diferencia']))
            ->values()
            ->toArray();
    }

    private function mapearFila(string $tipo, $r): array
    {
        $detalles = $this->obtenerExplicacionDetallada($tipo, $r->registro_diario_id);

        return [
            'key' => "{$tipo}_{$r->registro_diario_id}",
            'tipo' => $tipo,
            'registro_diario_id' => $r->registro_diario_id,
            'fecha' => $r->fecha,
            'nombre' => $r->nombre,
            'total_actual' => (float) $r->total_actual,
            'total_correcto' => (float) $r->total_correcto,
            'diferencia' => round((float) $r->total_correcto - (float) $r->total_actual, 2),
            'labores_horas' => $detalles['labores_horas'],
            'bonos_registrados' => $detalles['bonos_registrados'],
            'seleccionado' => false,
            'corregido' => false,
        ];
    }

    private function obtenerExplicacionDetallada(string $tipo, int $registroDiarioId): array
    {
        if ($tipo === 'CUADRILLA') {
            $laboresHoras = DB::table('cuad_detalles_horas')
                ->where('registro_diario_id', $registroDiarioId)
                ->pluck('codigo_labor')
                ->unique()
                ->toArray();

            $bonos = DB::table('cuad_bonos_actividades as ba')
                ->join('actividades as a', 'a.id', '=', 'ba.actividad_id')
                ->where('ba.registro_diario_id', $registroDiarioId)
                ->select('a.codigo_labor', 'a.nombre_labor', 'ba.total_bono')
                ->get();
        } else {
            $laboresHoras = DB::table('plan_detalles_horas')
                ->where('plan_reg_dia_id', $registroDiarioId)
                ->pluck('codigo_labor')
                ->unique()
                ->toArray();

            $bonos = DB::table('plan_actividad_bonos as pba')
                ->join('actividades as a', 'a.id', '=', 'pba.actividad_id')
                ->where('pba.registro_diario_id', $registroDiarioId)
                ->select('a.codigo_labor', 'a.nombre_labor', 'pba.total_bono')
                ->get();
        }

        $bonosMapeados = $bonos->map(function ($b) use ($laboresHoras) {
            $esValido = in_array($b->codigo_labor, $laboresHoras);
            return [
                'labor' => "[{$b->codigo_labor}] {$b->nombre_labor}",
                'monto' => (float) $b->total_bono,
                'valido' => $esValido,
                'motivo' => $esValido ? 'Labor presente en detalles de horas' : 'Labor eliminada o no coincide con horas registradas',
            ];
        })->toArray();

        return [
            'labores_horas' => implode(', ', $laboresHoras) ?: 'Sin horas registradas',
            'bonos_registrados' => $bonosMapeados,
        ];
    }

    public function updatedSeleccionTodos($valor)
    {
        foreach ($this->inconsistencias as $index => $item) {
            if (!$item['corregido']) {
                $this->inconsistencias[$index]['seleccionado'] = (bool) $valor;
            }
        }
    }

    public function corregirFila($index)
    {
        $item = $this->inconsistencias[$index] ?? null;
        if (!$item || $item['corregido']) {
            return;
        }

        $this->aplicarCorreccion($item);

        $this->inconsistencias[$index]['total_actual'] = $item['total_correcto'];
        $this->inconsistencias[$index]['diferencia'] = 0;
        $this->inconsistencias[$index]['seleccionado'] = false;
        $this->inconsistencias[$index]['corregido'] = true;

        $this->alert('success', 'Registro corregido correctamente');
    }

    public function corregirSeleccionados()
    {
        $corregidos = 0;

        foreach ($this->inconsistencias as $index => $item) {
            if ($item['seleccionado'] && !$item['corregido']) {
                $this->aplicarCorreccion($item);

                $this->inconsistencias[$index]['total_actual'] = $item['total_correcto'];
                $this->inconsistencias[$index]['diferencia'] = 0;
                $this->inconsistencias[$index]['seleccionado'] = false;
                $this->inconsistencias[$index]['corregido'] = true;
                $corregidos++;
            }
        }

        $this->seleccionTodos = false;

        if ($corregidos === 0) {
            $this->alert('warning', 'No seleccionó ningún registro para corregir');
            return;
        }

        $this->alert('success', "{$corregidos} registro(s) corregido(s) correctamente");
    }

    private function aplicarCorreccion(array $item): void
    {
        if ($item['tipo'] === 'CUADRILLA') {
            CuadRegistroDiario::where('id', $item['registro_diario_id'])
                ->update(['total_bono' => $item['total_correcto']]);
        } else {
            PlanRegistroDiario::where('id', $item['registro_diario_id'])
                ->update(['total_bono' => $item['total_correcto']]);
        }
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-bonificaciones-component');
    }
}