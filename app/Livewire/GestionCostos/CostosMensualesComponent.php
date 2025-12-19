<?php

namespace App\Livewire\GestionCostos;
use App\Models\CostoMensual;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CostosMensualesComponent extends Component
{
    use LivewireAlert;
    public $aniosDisponibles = [];
    public $selectedCategory = 'todos';
    public $selectedType = 'blanco';
    public $filtroAnio;
    public $filtroMes;
    public $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    protected $listeners = ['actualizarCostosMensualesTable' => 'obtenerDataEstadistica'];
    public $estadisticaData = [];
    public function mount()
    {
        // Obtener años disponibles
        $this->aniosDisponibles = CostoMensual::query()
            ->select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->toArray();

        // Año seleccionado: si filtro existe, usarlo; sino último año disponible
        $this->filtroAnio = $this->filtroAnio ?? ($this->aniosDisponibles[0] ?? date('Y'));

        // Obtener datos estadísticos iniciales
        $this->obtenerDataEstadistica();
    }
    public function updatedFiltroAnio(){
        $this->obtenerDataEstadistica();
    }
    public function obtenerDataEstadistica()
    {
        $anio = $this->filtroAnio;

        // Inicializar array de 12 meses con valores cero
        $estadistica = collect(range(1, 12))->map(function ($mes) use ($anio) {
            return [
                'anio' => $anio,
                'mes' => $mes,
                'blanco' => 0,
                'negro' => 0,
                'total' => 0,
            ];
        })->keyBy('mes')->toArray();

        // Obtener registros del año seleccionado
        $registros = CostoMensual::where('anio', $anio)->get();

        foreach ($registros as $item) {
            $blanco =
                $item->fijo_administrativo_blanco +
                $item->fijo_financiero_blanco +
                $item->fijo_gastos_oficina_blanco +
                $item->fijo_depreciaciones_blanco +
                $item->fijo_costo_terreno_blanco +
                $item->operativo_servicios_fundo_blanco +
                $item->operativo_mano_obra_indirecta_blanco;

            $negro =
                $item->fijo_administrativo_negro +
                $item->fijo_financiero_negro +
                $item->fijo_gastos_oficina_negro +
                $item->fijo_depreciaciones_negro +
                $item->fijo_costo_terreno_negro +
                $item->operativo_servicios_fundo_negro +
                $item->operativo_mano_obra_indirecta_negro;

            $estadistica[$item->mes] = [
                'anio' => $anio,
                'mes' => $item->mes,
                'blanco' => $blanco,
                'negro' => $negro,
                'total' => $blanco + $negro,
            ];
        }

        $this->estadisticaData = array_values($estadistica);
        $this->dispatch('refrescarTablaCostosMensuales',$this->estadisticaData);
    }
    public function render()
    {
        $filteredData = CostoMensual::query()
            ->when($this->filtroAnio, function ($query) {
                $query->where('anio', $this->filtroAnio);
            })
            ->when($this->filtroMes, function ($query) {
                $query->where('mes', $this->filtroMes);
            })
            ->orderBy('anio')
            ->orderBy('mes')
            ->paginate(20);
        return view('livewire.gestion-costos.costos-mensuales-component', [
            'filteredData' => $filteredData
        ]);
    }
}