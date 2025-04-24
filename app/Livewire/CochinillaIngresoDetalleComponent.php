<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use App\Support\FormatoHelper;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
//MODULO COCHINILLA INGRESO
class CochinillaIngresoDetalleComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
    public $observaciones;
    protected $listeners = ["agregarDetalle", "storeTableDataCochinillaIngreso"];
    public function mount()
    {
        $this->idTable = Str::random(10);
        $this->observaciones = CochinillaObservacion::get()->pluck('codigo')->toArray();

    }
    public function storeTableDataCochinillaIngreso($datos)
    {
        if (!$this->cochinillaIngreso) {
            return;
        }

        DB::beginTransaction();

        try {
            // Eliminar los detalles anteriores
            $this->cochinillaIngreso->detalles()->delete();

            $indice = 0;
            $data = [];

            foreach ($datos as $value) {
                // Validar campos requeridos
                if (empty($value['fecha']) || empty($value['total_kilos']) || empty($value['observacion'])) {
                    continue;
                }

                $fecha = FormatoHelper::parseFecha($value['fecha']);
                if (!$fecha) {
                    continue; // O lanzar una excepción si prefieres
                }

                $indice++;
                $subloteCodigo = $this->cochinillaIngreso->lote . '.' . $indice;

                $data[] = [
                    "cochinilla_ingreso_id" => $this->cochinillaIngreso->id,
                    "sublote_codigo" => $subloteCodigo,
                    "fecha" => $fecha,
                    "total_kilos" => $value['total_kilos'],
                    "observacion" => $value['observacion'],
                ];
            }

            // Insertar nuevos detalles
            CochinillaIngresoDetalle::insert($data);

            // Actualizar total_kilos del ingreso (reconsultando los detalles)
            $total = CochinillaIngresoDetalle::where('cochinilla_ingreso_id', $this->cochinillaIngreso->id)
                ->sum('total_kilos');

            $this->cochinillaIngreso->update([
                'total_kilos' => $total
            ]);

            $this->alert('success','Registro exitoso');
            $this->dispatch('detalleIngresoAgregado');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->alert('error',$e->getMessage());
        }
    }
    public function agregarDetalle($ingresoId)
    {
        $this->cochinillaIngreso = CochinillaIngreso::find($ingresoId);
        if ($this->cochinillaIngreso) {

            $detalle = $this->cochinillaIngreso->detalles->toArray();
            $this->mostrarFormulario = true;
            $this->dispatch('cargarData', $detalle);
        }

    }
    public function render()
    {
        return view('livewire.cochinilla-ingreso-detalle-component');
    }
}
