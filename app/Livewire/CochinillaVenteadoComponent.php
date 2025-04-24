<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaVenteado;
use DB;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaVenteadoComponent extends Component
{
    use WithPagination;
    public $lote;
    public $anioSeleccionado;
    public $campoSeleccionado;
    public $aniosDisponibles = [];
    public $verLotesSinIngresos = false;
    protected $listeners = ["venteadoAgregado"=> '$refresh'];
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
        // Mostrar huérfanos si está activado el toggle
        if ($this->verLotesSinIngresos) {
            // Obtenemos los venteados huérfanos (sin ingreso relacionado)
            $venteadosQuery = CochinillaVenteado::query()
                ->whereNotIn('lote', function ($q) {
                    $q->select('lote')->from('cochinilla_ingresos');
                });

            // Aplicamos filtros
            if ($this->lote) {
                $venteadosQuery->where('lote', $this->lote);
            }

            if ($this->anioSeleccionado) {
                $venteadosQuery->whereYear('fecha_proceso', $this->anioSeleccionado);
            }

            // Años disponibles
            $this->aniosDisponibles = CochinillaVenteado::whereNotIn('lote', function ($q) {
                $q->select('lote')->from('cochinilla_ingresos');
            })
                ->selectRaw('YEAR(fecha_proceso) as anio')
                ->groupBy(DB::raw('YEAR(fecha_proceso)'))
                ->pluck('anio')
                ->toArray();

            // Agrupamos por lote
            $venteadosPorLote = $venteadosQuery->get()->groupBy('lote');

            // Creamos "falsos" ingresos para usar en la tabla
            $cochinillaIngresos = $venteadosPorLote->map(function ($grupo, $lote) {
                $obj = new \stdClass();
                $obj->id = null;
                $obj->lote = $lote;
                $obj->fecha = null;
                $obj->fecha_proceso_venteado = optional($grupo->sortBy('fecha_proceso')->last())->fecha_proceso;
                $obj->campo = optional($grupo->first())->campo;

                $obj->total_kilos = null;
                $obj->total_venteado_kilos_ingresados = $grupo->sum('kilos_ingresado');
                $obj->total_venteado_limpia = $grupo->sum('limpia');
                $obj->total_venteado_basura = $grupo->sum('basura');
                $obj->total_venteado_polvillo = $grupo->sum('polvillo');
                $obj->total_venteado_total = $grupo->sum(fn($v) => $v->limpia + $v->basura + $v->polvillo);

                $total = $obj->total_venteado_total ?: 1; // Para evitar división por 0
                $obj->porcentaje_venteado_limpia = $obj->total_venteado_limpia * 100 / $total;
                $obj->porcentaje_venteado_basura = $obj->total_venteado_basura * 100 / $total;
                $obj->porcentaje_venteado_polvillo = $obj->total_venteado_polvillo * 100 / $total;
                $obj->diferencia = null;
                // Usamos la colección original como "venteados"
                $obj->venteados = $grupo;

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

            return view('livewire.cochinilla-venteado-component', [
                'cochinillaIngresos' => $paginator
            ]);
        }

        // Caso normal con ingresos
        $query = CochinillaIngreso::with(['venteados'])->whereHas('venteados');

        if ($this->lote) {
            $query->where('cochinilla_ingresos.lote', $this->lote);
        }

        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }

        // 🔄 Clonamos el query para sacar los años desde la relación 'venteados'
        $aniosQuery = (clone $query)
            ->join('cochinilla_venteados', 'cochinilla_ingresos.lote', '=', 'cochinilla_venteados.lote')
            ->select(DB::raw('YEAR(cochinilla_venteados.fecha_proceso) as anio'))
            ->groupBy(DB::raw('YEAR(cochinilla_venteados.fecha_proceso)'))
            ->pluck('anio')
            ->toArray();

        $this->aniosDisponibles = $aniosQuery;

        // 💡 Aquí ordenamos el query original
        $cochinillaIngresos = $query->orderBy('lote', 'desc');

        // 🔍 Si hay filtro de año, aplicamos sobre la relación 'venteados'
        if ($this->anioSeleccionado) {
            $query->whereHas('venteados', function ($q) {
                $q->whereYear('fecha_proceso', $this->anioSeleccionado);
            });
        }

        // 🔚 Paginamos
        $cochinillaIngresos = $query->paginate(15);

        return view('livewire.cochinilla-venteado-component', [
            'cochinillaIngresos' => $cochinillaIngresos
        ]);
    }
}
