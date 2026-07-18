<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use App\Services\CochinillaIngresoServicio;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

//MODULO COCHINILLA INGRESO - WIZARD (Lote + Sublotes)
class CochinillaIngresoDetalleComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $step = 1; // 1: buscar lote, 2: datos del lote, 3: sublotes (handsontable)

    // Paso 1
    public $loteBuscado;

    // Contexto cargado / en edición
    public $cochinillaIngreso; // instancia de CochinillaIngreso o null si es nuevo
    public $esNuevo = false;

    // Paso 2 (datos del lote)
    public $campoSeleccionado;
    public $area;
    public $campania; // instancia de CampoCampania
    public $fecha;
    public $observacionSeleccionada;

    // Paso 3 (handsontable)
    public $idTable;
    public $observaciones; // catálogo completo (paso 2)
    public $observacionesCodigos; // solo códigos (dropdown handsontable)
    public $campos = [];

    protected $listeners = [
        'agregarIngreso',
        'editarIngreso',
        'storeTableDataCochinillaIngreso',
    ];

    public function mount()
    {
        $this->idTable = 'table_' . Str::random(10);
        $this->campos = Campo::listar();
        $this->observaciones = CochinillaObservacion::all();
        $this->observacionesCodigos = $this->observaciones->pluck('codigo')->toArray();
    }

    /* ==========================
     *  APERTURA DEL WIZARD
     * ========================== */

    public function agregarIngreso()
    {
        $this->resetWizard();
        $this->loteBuscado = CochinillaIngresoServicio::generarCodigoSiguiente();
        $this->mostrarFormulario = true;
    }

    public function editarIngreso($lote)
    {
        $this->resetWizard();
        $this->loteBuscado = $lote;
        $this->mostrarFormulario = true;
        $this->buscarLote();
    }

    public function resetWizard()
    {
        $this->resetErrorBag();
        $this->reset([
            'step',
            'loteBuscado',
            'cochinillaIngreso',
            'esNuevo',
            'campoSeleccionado',
            'area',
            'campania',
            'fecha',
            'observacionSeleccionada',
        ]);
        $this->step = 1;
    }

    /* ==========================
     *  PASO 1: BUSCAR LOTE
     * ========================== */

    public function buscarLote()
    {
        $this->validate([
            'loteBuscado' => 'required|integer|min:1',
        ]);

        $ingreso = CochinillaIngreso::where('lote', $this->loteBuscado)->first();

        if ($ingreso) {
            $this->cargarIngresoExistente($ingreso);
            $this->step = 3;
        } else {
            $this->prepararNuevoIngreso();
            $this->step = 2;
        }
    }

    protected function cargarIngresoExistente(CochinillaIngreso $ingreso)
    {
        $this->cochinillaIngreso = $ingreso;
        $this->esNuevo = false;

        $this->campoSeleccionado = $ingreso->campo;
        $this->area = $ingreso->area;
        $this->campania = $ingreso->campoCampania;
        $this->fecha = $ingreso->fecha;
        $this->observacionSeleccionada = $ingreso->observacion;

        $this->dispatch('cargarData', $ingreso->detalles->toArray());
    }

    protected function prepararNuevoIngreso()
    {
        $this->cochinillaIngreso = null;
        $this->esNuevo = true;

        $this->campoSeleccionado = null;
        $this->area = null;
        $this->campania = null;
        $this->fecha = now()->format('Y-m-d');
        $this->observacionSeleccionada = null;
    }

    /* ==========================
     *  PASO 2: DATOS DEL LOTE
     * ========================== */

    public function updatedCampoSeleccionado($valorNuevoCampo)
    {
        $campo = Campo::where('nombre', $valorNuevoCampo)->first();
        $this->area = $campo?->area;
        $this->cargarCampania();
    }

    public function updatedFecha()
    {
        $this->cargarCampania();
    }

    protected function cargarCampania()
    {
        if ($this->campoSeleccionado && $this->fecha) {
            $this->campania = CampoCampania::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        } else {
            $this->campania = null;
        }
    }

    public function guardarPaso2()
    {
        $this->validate([
            'campoSeleccionado' => 'required|exists:campos,nombre',
            'fecha' => 'required|date',
            'observacionSeleccionada' => 'required',
        ]);

        if (!$this->campania) {
            return $this->alert('error', 'No hay campañas disponibles para este campo y fecha.');
        }

        try {
            $this->cochinillaIngreso = CochinillaIngreso::updateOrCreate(
                ['lote' => $this->loteBuscado],
                [
                    'campo' => $this->campoSeleccionado,
                    'area' => $this->area,
                    'campo_campania_id' => $this->campania->id,
                    'observacion' => $this->observacionSeleccionada,
                    'fecha' => $this->fecha,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return $this->alert('error', 'Ese número de lote ya fue registrado por otra persona justo ahora. Vuelve al paso 1 e intenta con otro número.');
            }
            throw $e;
        }

        $this->esNuevo = false;
        $this->dispatch('cargarData', $this->cochinillaIngreso->detalles->toArray());
        $this->step = 3;
    }

    public function volverAPaso2()
    {
        $this->step = 2;
    }

    /* ==========================
     *  PASO 3: SUBLOTES (HANDSONTABLE)
     * ========================== */

    public function storeTableDataCochinillaIngreso($datos)
    {
        if (!$this->cochinillaIngreso) {
            return;
        }

        DB::beginTransaction();

        try {
            $this->cochinillaIngreso->detalles()->delete();

            $indice = 0;
            $data = [];
            $ultimaFecha = null;
            $ultimaObservacion = null;

            foreach ($datos as $value) {
                if (empty($value['fecha']) || empty($value['total_kilos']) || empty($value['observacion'])) {
                    continue;
                }

                $fecha = FormatoHelper::parseFecha($value['fecha']);
                if (!$fecha) {
                    continue;
                }

                $indice++;
                $subloteCodigo = $this->cochinillaIngreso->lote . '.' . $indice;
                $ultimaFecha = $fecha;
                $ultimaObservacion = $value['observacion'];

                $data[] = [
                    'cochinilla_ingreso_id' => $this->cochinillaIngreso->id,
                    'sublote_codigo' => $subloteCodigo,
                    'fecha' => $fecha,
                    'total_kilos' => $value['total_kilos'],
                    'observacion' => $value['observacion'],
                ];
            }

            if (!empty($data)) {
                CochinillaIngresoDetalle::insert($data);
            }

            $total = CochinillaIngresoDetalle::where('cochinilla_ingreso_id', $this->cochinillaIngreso->id)
                ->sum('total_kilos');

            $actualizacion = ['total_kilos' => $total];
            if ($ultimaFecha) {
                $actualizacion['fecha'] = $ultimaFecha;
                $this->fecha = $ultimaFecha;
            }
            if ($ultimaObservacion) {
                $actualizacion['observacion'] = $ultimaObservacion;
                $this->observacionSeleccionada = $ultimaObservacion;
            }

            $this->cochinillaIngreso->update($actualizacion);
            $this->cochinillaIngreso->refresh();

            $this->alert('success', 'Registro exitoso');
            $this->dispatch('detalleIngresoAgregado');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->alert('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cochinilla-ingreso-detalle-component');
    }
}