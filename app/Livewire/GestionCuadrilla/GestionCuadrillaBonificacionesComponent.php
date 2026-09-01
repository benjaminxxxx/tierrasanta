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
        $this->actividades = Actividad::where('fecha', $this->fecha)->get();

    }
    public function buscarInconsistencias()
    {
        $this->mostrarInconsistencias = true;
        $this->seleccionTodos = false;

        $cuadrilla = DB::table('cuad_registros_diarios as rd')
            ->leftJoin(
                DB::raw('(SELECT registro_diario_id, SUM(total_bono) as suma_bono FROM cuad_bonos_actividades GROUP BY registro_diario_id) as sb'),
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
            ->whereRaw('ABS(rd.total_bono - COALESCE(sb.suma_bono, 0)) > 0.01')
            ->get()
            ->map(fn($r) => $this->mapearFila('CUADRILLA', $r));

        $planilla = DB::table('plan_registros_diarios as rd')
            ->leftJoin(
                DB::raw('(SELECT registro_diario_id, SUM(total_bono) as suma_bono FROM plan_actividad_bonos GROUP BY registro_diario_id) as sb'),
                'sb.registro_diario_id',
                '=',
                'rd.id'
            )
            // 1. Unimos con la tabla intermedia del detalle mensual
            ->join('plan_mensual_detalles as pmd', 'pmd.id', '=', 'rd.plan_det_men_id')
            // 2. Unimos con la tabla de empleados usando la FK que está en el detalle mensual
            ->join('plan_empleados as e', 'e.id', '=', 'pmd.plan_empleado_id')
            ->select(
                'rd.id as registro_diario_id',
                'rd.fecha',
                'rd.total_bono as total_actual',
                DB::raw('COALESCE(sb.suma_bono, 0) as total_correcto'),
                // Puedes tomar los nombres directo de pmd.nombres o concatenar desde e.nombres/apellidos
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
        return [
            'tipo' => $tipo,
            'registro_diario_id' => $r->registro_diario_id,
            'fecha' => $r->fecha,
            'nombre' => $r->nombre,
            'total_actual' => (float) $r->total_actual,
            'total_correcto' => (float) $r->total_correcto,
            'diferencia' => round((float) $r->total_correcto - (float) $r->total_actual, 2),
            'seleccionado' => false,
            'corregido' => false,
        ];
    }

    // Se dispara automáticamente al cambiar el checkbox "seleccionar todos" (wire:model.live)
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