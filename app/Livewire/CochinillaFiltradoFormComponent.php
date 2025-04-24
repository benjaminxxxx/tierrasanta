<?php

namespace App\Livewire;

use App\Models\CochinillaFiltrado;
use App\Models\CochinillaIngreso;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaFiltradoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
    protected $listeners = ["agregarFiltrado", "storeTableDataCochinillaIngresoFiltrado"];
    public function mount()
    {
        $this->idTable = Str::random(10);
    }
    public function storeTableDataCochinillaIngresoFiltrado($datos){
        
        DB::beginTransaction();

        try {
            if($this->cochinillaIngreso){
                $this->cochinillaIngreso->filtrados()->delete();
            }
            
            $indice = 0;
            $data = [];

            foreach ($datos as $value) {
                // Validar campos requeridos
                if (empty($value['fecha_proceso']) || empty($value['kilos_ingresados'])) {
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
                $kilos_ingresados = $value['kilos_ingresados'] ?? 0;
                $primera = $value['primera'] ?? 0;
                $segunda = $value['segunda'] ?? 0;
                $tercera = $value['tercera'] ?? 0;
                $piedra = $value['piedra'] ?? 0;
                $basura = $kilos_ingresados - ($primera + $segunda + $tercera + $piedra);

                $data[] = [
                    'lote' => $lote,
                    'fecha_proceso'=> $fecha,
                    'kilos_ingresados'=> $kilos_ingresados,
                    'primera' => $primera,
                    'segunda' => $segunda,
                    'tercera' => $tercera,
                    'piedra' => $piedra,
                    'basura' => $basura
                ];
            }

            // Insertar nuevos detalles
            CochinillaFiltrado::insert($data);
            $this->alert('success','Registro exitoso');
            if($this->cochinillaIngreso){
                $this->agregarFiltrado($this->cochinillaIngreso->id);
            }else{
                $this->mostrarFormulario = false;
            }
            
            $this->dispatch('filtradoAgregado');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->alert('error',$e->getMessage());
        }
    }
    public function agregarFiltrado($ingresoId = null){
        if($ingresoId){
            $this->cochinillaIngreso = CochinillaIngreso::find($ingresoId);
            if ($this->cochinillaIngreso) {
                $filtrados = $this->cochinillaIngreso->filtrados->toArray();
                $this->dispatch('cargarDataFiltrado', $filtrados);
            }
        }
        $this->mostrarFormulario = true;
    }
    public function render()
    {
        return view('livewire.cochinilla-filtrado-form-component');
    }
}
