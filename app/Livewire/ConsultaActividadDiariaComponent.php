<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\ReporteDiario;
use App\Models\ReporteDiarioDetalle;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
class ConsultaActividadDiariaComponent extends Component
{
    use LivewireAlert;
    public $campaniaId;
    public $campo;
    public $campania;
    public $codigo;
    public $descripcion;
    public $listaActividades = [];
    public function mount($campaniaId)
    {
        $this->campaniaId = $campaniaId;
        $this->campania = CampoCampania::find($this->campaniaId);
        if ($this->campania) {
            $this->campo = $this->campania->campo;
        }
    }
    public function buscar()
    {
        if (!$this->campania) {
            return;
        }
    
        $fechaDesde = $this->campania->fecha_inicio;
        $fechaHasta = $this->campania->fecha_fin;
    
        $this->listaActividades = DB::table('reporte_diario_detalles')
            ->join('reporte_diarios', 'reporte_diario_detalles.reporte_diario_id', '=', 'reporte_diarios.id')
            ->join('labores', 'reporte_diario_detalles.labor', '=', 'labores.id')
            ->when($this->campo, function ($query) {
                return $query->where('reporte_diario_detalles.campo', $this->campo);
            })
            ->when($this->descripcion, function ($query) {
                return $query->where('labores.nombre_labor', 'like', '%' . $this->descripcion . '%');
            })
            ->when($this->codigo, function ($query) {
                return $query->where('reporte_diario_detalles.labor', $this->codigo);
            })
            ->when($fechaHasta, function ($query) use ($fechaDesde, $fechaHasta) {
                return $query->whereBetween('reporte_diarios.fecha', [$fechaDesde, $fechaHasta]);
            }, function ($query) use ($fechaDesde) {
                return $query->where('reporte_diarios.fecha', '>=', $fechaDesde);
            })
            ->select(
                'reporte_diarios.fecha',
                'reporte_diario_detalles.labor',
                'reporte_diario_detalles.campo',
                'labores.nombre_labor',
                DB::raw('count(*) as cantidad_registros'),
                DB::raw('count(distinct reporte_diarios.documento) as personas')
            )
            ->groupBy(
                'reporte_diarios.fecha',
                'reporte_diario_detalles.labor',
                'reporte_diario_detalles.campo',
                'labores.nombre_labor'
            )
            ->orderBy('reporte_diarios.fecha', 'desc')
            ->limit(10)
            ->get();
    }
    

    public function render()
    {
        return view('livewire.consulta-actividad-diaria-component');
    }
}
