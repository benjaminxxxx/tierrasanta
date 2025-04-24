<?php

namespace App\Livewire;

use App\Models\CochinillaFiltrado;
use App\Models\CochinillaIngreso;
use DB;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaFiltradoComponent extends Component
{
    use WithPagination;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["filtradoAgregado"=> '$refresh'];
    public function updatedLote(){
        $this->resetPage();
    }
    public function updatedCampoSeleccionado(){
        $this->resetPage();
    }
    public function updatedAnioSeleccionado(){
        $this->resetPage();
    }
    public function render()
    {
        #region Mostrar huérfanos si está activado el toggle
        if ($this->verLotesSinIngresos) {
            // Obtenemos los venteados huérfanos (sin ingreso relacionado)
            $filtradosQuery = CochinillaFiltrado::query()
                ->whereNotIn('lote', function ($q) {
                    $q->select('lote')->from('cochinilla_ingresos');
                });

            // Aplicamos filtros
            if ($this->lote) {
                $filtradosQuery->where('lote', $this->lote);
            }

            if ($this->anioSeleccionado) {
                $filtradosQuery->whereYear('fecha_proceso', $this->anioSeleccionado);
            }

            // Años disponibles
            $this->aniosDisponibles = CochinillaFiltrado::whereNotIn('lote', function ($q) {
                $q->select('lote')->from('cochinilla_ingresos');
            })
                ->selectRaw('YEAR(fecha_proceso) as anio')
                ->groupBy(DB::raw('YEAR(fecha_proceso)'))
                ->pluck('anio')
                ->toArray();

            // Agrupamos por lote
            $filtradosPorLote = $filtradosQuery->get()->groupBy('lote');

            // Creamos "falsos" ingresos para usar en la tabla
            $cochinillaIngresos = $filtradosPorLote->map(function ($grupo, $lote) {
                $obj = new \stdClass();
                $obj->id = null;
                $obj->lote = $lote;
                $obj->fecha = null;
                $obj->fecha_proceso_filtrado = optional($grupo->sortBy('fecha_proceso')->last())->fecha_proceso;
                $obj->campo = optional($grupo->first())->campo;

                $obj->total_kilos = null;
                $obj->total_filtrado_kilos_ingresados = $grupo->sum('kilos_ingresados');
                $obj->total_filtrado_primera = $grupo->sum('primera');
                $obj->total_filtrado_segunda = $grupo->sum('segunda');
                $obj->total_filtrado_tercera = $grupo->sum('tercera');
                $obj->total_filtrado_piedra = $grupo->sum('piedra');
                $obj->total_filtrado_basura = $grupo->sum('basura');
                $obj->total_filtrado_total = $grupo->sum(fn($v) => $v->primera + $v->segunda + $v->tercera + $v->piedra + $v->basura);

                $total = $obj->total_filtrado_total ?: 1; // Para evitar división por 0
                $obj->porcentaje_filtrado_primera = $obj->total_filtrado_primera * 100 / $total;
                $obj->porcentaje_filtrado_segunda = $obj->total_filtrado_segunda * 100 / $total;
                $obj->porcentaje_filtrado_tercera = $obj->total_filtrado_tercera * 100 / $total;
                $obj->porcentaje_filtrado_piedra = $obj->total_filtrado_piedra * 100 / $total;
                $obj->porcentaje_filtrado_basura = $obj->total_filtrado_basura * 100 / $total;
                $obj->diferencia_filtrado = null;
                // Usamos la colección original como "venteados"
                $obj->filtrados = $grupo;

                return $obj;
            })->sortByDesc('lote')->values();

            // Manualmente paginamos
            $perPage = 15;
            $currentPage = request()->get('page', 1);
            $pagedData = $cochinillaIngresos->forPage($currentPage, $perPage);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $pagedData,
                $cochinillaIngresos->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('livewire.cochinilla-filtrado-component', [
                'cochinillaIngresos' => $paginator
            ]);
        }
        #endregion

        #region Caso normal con ingresos
        $query = CochinillaIngreso::with(['filtrados'])->whereHas('filtrados');

        if ($this->lote) {
            $query->where('cochinilla_ingresos.lote', $this->lote);
        }

        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }

        // 🔄 Clonamos el query para sacar los años desde la relación 'venteados'
        $aniosQuery = (clone $query)
            ->join('cochinilla_filtrados', 'cochinilla_ingresos.lote', '=', 'cochinilla_filtrados.lote')
            ->select(DB::raw('YEAR(cochinilla_filtrados.fecha_proceso) as anio'))
            ->groupBy(DB::raw('YEAR(cochinilla_filtrados.fecha_proceso)'))
            ->pluck('anio')
            ->toArray();

        $this->aniosDisponibles = $aniosQuery;

        // 💡 Aquí ordenamos el query original
        $cochinillaIngresos = $query->orderBy('lote', 'desc');

        // 🔍 Si hay filtro de año, aplicamos sobre la relación 'filtrados'
        if ($this->anioSeleccionado) {
            $query->whereHas('filtrados', function ($q) {
                $q->whereYear('fecha_proceso', $this->anioSeleccionado);
            });
        }

        // 🔚 Paginamos
        $cochinillaIngresos = $query->paginate(15);

        return view('livewire.cochinilla-filtrado-component',[
            'cochinillaIngresos' => $cochinillaIngresos
        ]);

        #endregion
    }
}
