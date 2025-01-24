<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CamposCampaniasConsumo;
use App\Models\CategoriaProducto;
use App\Models\ResumenConsumoProductos;
use App\Services\CampaniaServicio;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campanias;
    public $campos;
    public $campoSeleccionado;
    protected $listeners = ['GuardarInformacion', 'confirmarEliminar', 'campaniaInsertada' => 'obtenerRegistros'];

    public function mount($campo = null)
    {
        $this->campos = Campo::orderBy('orden')->get();
        if ($campo) {
            $this->campoSeleccionado = $campo;
            $this->obtenerRegistros();
        }
    }
    public function updatedCampoSeleccionado()
    {

        $this->obtenerRegistros();
    }
    public function obtenerRegistros()
    {
        if (!$this->campoSeleccionado) {
            $this->campanias = null;
            return;
        }

        $campo = Campo::find($this->campoSeleccionado);

        if (!$campo) {
            return $this->alert('error', 'El campo no existe.');
        }

        $this->campanias = $campo->campanias()->orderBy('fecha_inicio', 'desc')->get();
    }

    public function eliminarCampania($campaniaId)
    {
        $this->alert('question', '¿Está seguro(a) que desea eliminar la campaña?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'campaniaId' => $campaniaId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $campaniaId = $data['campaniaId'];
        $campania = CampoCampania::find($campaniaId);
        if ($campania) {
            $campaniaAnterior = CampoCampania::whereDate('fecha_inicio', '<', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
            if ($campaniaAnterior) {
                //si hay un registro anterior, debemos actualizar su fecha de fin, pero actualizaremos solo en caso haya una campaña posterior
                $campaniaPosterior = CampoCampania::whereDate('fecha_inicio', '>', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
                if ($campaniaPosterior) {
                    $fecha = Carbon::parse($campaniaPosterior->fecha_inicio)->addDay(-1);
                    $campaniaAnterior->update([
                        'fecha_fin' => $fecha
                    ]);
                } else {
                    //cuando no hay fecha siguiente o posterior, quiere decir que aun no debe haber fecha_fin
                    $campaniaAnterior->update([
                        'fecha_fin' => null
                    ]);
                }
            }
        }
        $campania->delete();
        $this->obtenerRegistros();
        $this->alert('success', 'Registros Eliminados Correctamente.');
    }
    public function actualizarGastosConsumo($campaniaId)
    {

        try {

            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->actualizarGastosyConsumos();

            $campania = CampoCampania::find($campaniaId);
            if (!$campania) {
                return $this->alert('error', 'La campaña no existe.');
            }
            
            $campania->consumos()->delete();
            $campania->consumo()->delete();
            $fecha_inicio = $campania->fecha_inicio;
            $fecha_fin = $campania->fecha_fin;
            $campo = $campania->campo;

            $query = AlmacenProductoSalida::whereDate('fecha_reporte', '>=', $fecha_inicio);
            if ($fecha_fin) {
                $query->whereDate('fecha_reporte', '<=', $fecha_fin);
            }
            $registros = $query->where('campo_nombre', $campo)->get();
            if ($registros) {
                foreach ($registros as $registro) {

                    ResumenConsumoProductos::create([
                        'fecha' => $registro->fecha_reporte,
                        'campo' => $registro->campo_nombre,
                        'producto' => $registro->producto->nombre_completo,
                        'categoria' => $registro->producto->categoria->nombre,
                        'categoria_id' => $registro->producto->categoria_id,
                        'cantidad' => $registro->cantidad,
                        'total_costo' => $registro->total_costo,
                        'campos_campanias_id' => $campania->id
                    ]);
                }

                $categoriaProductos = CategoriaProducto::all();
                if ($categoriaProductos) {
                    foreach ($categoriaProductos as $categoriaProducto) {
                        $totalConsumido = ResumenConsumoProductos::where('campos_campanias_id', $campania->id)
                            ->where('categoria_id', $categoriaProducto->id)
                            ->sum('total_costo');
                        CamposCampaniasConsumo::create([
                            'campos_campanias_id' => $campania->id,
                            'categoria_id' => $categoriaProducto->id,
                            'monto' => $totalConsumido,
                        ]);
                    }
                }
                $this->alert('success', 'Gastos y Consumos actualizados correctamente.');
            }
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al Actualizar los Gastos y Consumos.');
        }
    }

    public function render()
    {
        return view('livewire.campo-campania-component');
    }
}
