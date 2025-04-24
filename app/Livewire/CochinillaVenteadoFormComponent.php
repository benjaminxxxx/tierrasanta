<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaVenteado;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaVenteadoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
    protected $listeners = ["agregarVenteado", "storeTableDataCochinillaIngresoVenteado"];
    public function mount()
    {
        $this->idTable = Str::random(10);
    }
    public function storeTableDataCochinillaIngresoVenteado($datos){
      
        DB::beginTransaction();

        try {
            // Eliminar los venteados anteriores
            if($this->cochinillaIngreso){
                $this->cochinillaIngreso->venteados()->delete();
            }            

            $indice = 0;
            $data = [];

            foreach ($datos as $value) {
                // Validar campos requeridos
                if (empty($value['fecha_proceso']) || empty($value['kilos_ingresado'])) {
                    continue;
                }

                $fecha = FormatoHelper::parseFecha($value['fecha_proceso']);
                if (!$fecha) {
                    continue; // O lanzar una excepción si prefieres
                }

                $indice++;

                $lote = $value['lote'] ?? null;
                if($this->cochinillaIngreso){
                    $lote = $this->cochinillaIngreso->lote;
                }
                $kilos_ingresado = $value['kilos_ingresado'] ?? 0;
                $limpia = $value['limpia'] ?? 0;
                $polvillo = $value['polvillo'] ?? 0;
                $basura = $kilos_ingresado - ($limpia + $polvillo);

                if($lote){
                    $data[] = [
                        "lote" => $lote,
                        "fecha_proceso" => $fecha,
                        "kilos_ingresado" => $kilos_ingresado,
                        "limpia" => $limpia,
                        "basura" => $basura,
                        "polvillo" => $polvillo,
                    ];
                }
                
            }

            // Insertar nuevos detalles
            CochinillaVenteado::insert($data);
            $this->alert('success','Registro exitoso');
            if($this->cochinillaIngreso){
                $this->agregarVenteado($this->cochinillaIngreso->id);
            }else{
                $this->mostrarFormulario = false;
            }
            
            $this->dispatch('venteadoAgregado');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->alert('error',$e->getMessage());
        }
    }
    public function agregarVenteado($ingresoId = null){
        if($ingresoId){
            $this->cochinillaIngreso = CochinillaIngreso::find($ingresoId);
            if ($this->cochinillaIngreso) {
                $venteados = $this->cochinillaIngreso->venteados->toArray();
                
                $this->dispatch('cargarDataVenteado', $venteados);
            }
        }
        $this->mostrarFormulario = true;
    }
    public function render()
    {
        return view('livewire.cochinilla-venteado-form-component');
    }
}
