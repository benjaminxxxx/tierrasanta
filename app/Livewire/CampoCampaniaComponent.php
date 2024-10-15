<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
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
    public $fecha;
    public $fechasRegistradas;
    public $mostrarNuevoForm = false;
    public $existeAnterior = false;
    public $lotes = [];
    public $fechaAEliminar;
    public $usarInformacionAnterior = true;
    protected $listeners = ['GuardarInformacion', 'confirmarEliminar'];

    public function mount()
    {
        $ultimaFecha = CampoCampania::orderBy('fecha_vigencia', 'desc')->first();
        if ($ultimaFecha) {
            $this->fecha = $ultimaFecha->fecha_vigencia;
        } else {
            $this->fecha = Carbon::now()->format('Y-m-d');
        }

        $this->obtenerRegistros();
    }
    public function obtenerRegistros()
    {
        if ($this->fecha) {
            $this->campanias = CampoCampania::whereDate('fecha_vigencia', $this->fecha)->get();
            $this->existeAnterior = $this->campanias->count() > 0;
            $this->lotes = [];

            if ($this->campanias->count() == 0) {
                //por primera vez, agregar informacion al grid
                $this->lotes = Campo::orderBy('orden')->get()
                    ->map(function ($lote) {
                        return [
                            'lote' => $lote->nombre,
                            'area' => 0,
                            'campania' => ''
                        ];
                    })->toArray();
            } else {
                $this->lotes = $this->campanias->map(function ($compania) {
                    return [
                        'lote' => $compania->lote,
                        'area' => $compania->area,
                        'campania' => $compania->campania
                    ];
                });
            }
            $this->dispatch('renderTable', $this->lotes);
        }
    }
    
    public function agregarVigencia(){
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->mostrarNuevoForm = true;
    }
    public function crearVigencia()
    {
        try {
            $existeRegistroSegunFecha = CampoCampania::whereDate('fecha_vigencia', $this->fecha)->exists();
            if ($existeRegistroSegunFecha) {
                throw new Exception("La fecha ya tiene registros elija otra fecha");
            }

            $ultimosRegistros = [];

            if ($this->usarInformacionAnterior) {
                $ultimaFechaRegistro = CampoCampania::orderBy('fecha_vigencia', 'desc')->first();
                if (!$ultimaFechaRegistro) {
                    throw new Exception("No existen datos en fechas anteriores para replicar, debe desactivar esta opción.");
                }
                $ultimaFecha = $ultimaFechaRegistro->fecha_vigencia;
                $ultimosRegistros = CampoCampania::whereDate('fecha_vigencia', $ultimaFecha)->get()->keyBy('lote')->toArray();
            }

            $datos = Campo::orderBy('orden')->get()
                ->map(function ($lote) use ($ultimosRegistros) {

                    $area = array_key_exists($lote->nombre, $ultimosRegistros) ? $ultimosRegistros[$lote->nombre]['area'] : null;
                    $campania = array_key_exists($lote->nombre, $ultimosRegistros) ? $ultimosRegistros[$lote->nombre]['campania'] : null;

                    return [$lote->nombre, $area, $campania];
                })->toArray();

            $this->mostrarNuevoForm = false;
            $this->GuardarInformacion($datos);
            $this->obtenerRegistros();
        } catch (\Throwable $ex) {
            return $this->alert('error', $ex->getMessage());
        }
    }
    public function cambiarFechaA($fecha)
    {
        $this->fecha = $fecha;
        $this->obtenerRegistros();
    }
    public function eliminarFecha($fecha)
    {

        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'fecha' => $fecha,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $fecha = $data['fecha'];
        CampoCampania::whereDate('fecha_vigencia', $fecha)->delete();
        $this->alert('success', 'Registros Eliminados Correctamente.');
    }
    public function render()
    {
        $this->fechasRegistradas = CampoCampania::select('fecha_vigencia')
            ->distinct()
            ->orderBy('fecha_vigencia', 'desc')
            ->pluck('fecha_vigencia')
            ->map(function ($fecha) {
                return Carbon::parse($fecha)->format('Y-m-d'); // Cambia el formato a Y-m
            })
            ->toArray();



        return view('livewire.campo-campania-component');
    }
    public function GuardarInformacion($datos)
    {
        /*
        if (is_array($this->fechasRegistradas) && count($this->fechasRegistradas) > 0) {
            $ultimaFecha = $this->fechasRegistradas[0]; //ultima fecha
        } else {
            //No hay ninguna fecha registrada, entrar como null
        }*/
        try {

            $nombresCampos = Campo::pluck('nombre')->toArray();
            $validatedData = [];

            foreach ($datos as $indice => $entry) {
                // Validar si el campo (nombre) existe en el array de nombres de campos
                if (!in_array($entry[0], $nombresCampos)) {
                    throw new Exception("el campo de la fila " . $indice . " no es un campo registrado");
                }

                // Validar que el área sea un número (puede ser decimal o entero)
                if (!is_numeric($entry[1])) {
                    $entry[1] = null;
                }

                // Validar que la campaña sea un string no vacío
                if (empty($entry[2]) || !is_string($entry[2])) {
                    $entry[2] = null;
                }

                // 4. Si pasa todas las validaciones, agregarlo al array de datos validados
                $validatedData[] = [
                    'lote' => $entry[0],
                    'area' => $entry[1],
                    'campania' => $entry[2],
                    'fecha_vigencia' => $this->fecha, // Usamos la fecha de la clase o de donde venga
                ];
            }
            DB::transaction(function () use ($validatedData) {
                CampoCampania::where('fecha_vigencia', $this->fecha)->delete(); // Eliminar registros previos

                // 6. Insertar los nuevos registros
                if (!empty($validatedData)) {
                    CampoCampania::insert($validatedData); // Inserción masiva
                    $this->alert('success', 'Registros Creados Correctamente.');
                }
            });
        } catch (Exception $ex) {
            return $this->alert('error', $ex->getMessage());
        } catch (QueryException $ex) {
            return $this->alert('error', $ex->getMessage());
        }
    }
}
