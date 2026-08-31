<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\CuadTramoLaboral;
use App\Models\CuadTramoLaboralCuadrillero;
use App\Models\CuadTramoLaboralGrupo;
use App\Models\CuaGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalTramoAgregarCuadrilleroComponent extends Component
{
    use LivewireAlert;
    public $tramoLaboral;
    public $mostrarAgregarCuadrillero = false;
    public $grupos = [];
    public $codigo_grupo;
    public $listaCuadrilleros = [];
    public $cuadrillerosAgregados = [];
    protected $listeners = ['agregarCuadrillerosEnTramo', 'grupoRegistrado'];
    public function mount($tramoId)
    {

        $this->tramoLaboral = CuadTramoLaboral::find($tramoId);
        $this->listarGrupos();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $this->grupos->first()->codigo;
        }
        $this->listaCuadrilleros = Cuadrillero::select('id', 'nombres', 'dni')
            ->orderBy('nombres')
            ->get()
            ->toArray();
    }
    public function listarGrupos()
    {
        $this->grupos = CuaGrupo::all();
    }
    public function grupoRegistrado($grupo)
    {
        $codigoGrupo = $grupo['codigo'] ?? null;
        if (!$codigoGrupo) {
            return;
        }

        $this->listarGrupos();
        $this->codigo_grupo = $codigoGrupo;
        $this->obtenerCuadrillerosAgregados();
    }
    public function updatedCodigoGrupo()
    {
        $this->obtenerCuadrillerosAgregados();
    }

    public function obtenerCuadrillerosAgregados()
    {
        if (!$this->codigo_grupo) {
            $this->cuadrillerosAgregados = [];
            return;
        }
        $grupoEnTramoLaboral = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $this->tramoLaboral->id)
            ->where('codigo_grupo', $this->codigo_grupo)
            ->with(['cuadrilleros', 'cuadrilleros.cuadrillero'])
            ->first();

        if (!$grupoEnTramoLaboral) {
            $this->cuadrillerosAgregados = [];
            return;
        }
        $this->cuadrillerosAgregados = $grupoEnTramoLaboral->cuadrilleros()
            ->orderBy('orden')
            ->get(['cuadrillero_id', 'orden'])
            ->map(function ($cuadOrdenSemanal) {
                return [
                    'id' => $cuadOrdenSemanal->cuadrillero_id,
                    'nombres' => $cuadOrdenSemanal->cuadrillero->nombres
                ];
            })
            ->toArray();
    }
    public function resetForm()
    {

    }
    public function agregarListaAgregada()
    {
        // Usar transacción para asegurar la integridad de la BD
        DB::beginTransaction();

        try {
            if (!$this->codigo_grupo) {
                throw new Exception("No ha elegido ningún grupo");
            }

            // IDs de cuadrilleros que se registrarán en esta ejecución
            $idsNuevos = [];

            // Verificar si existe el grupo en el tramoLaboral
            $grupo = $this->registrarGrupoEnTramoLaboral($this->tramoLaboral, $this->codigo_grupo);

            $orden = 0;
            foreach ($this->cuadrillerosAgregados as $cuadrillero) {
                $nombres = trim($cuadrillero['nombres'] ?? '');
                $cuadrilleroId = $cuadrillero['id'] ?? null;
                $orden++;

                if (!$cuadrilleroId) {
                    // Buscar cuadrillero por nombre o crear si no existe
                    $cuadrilleroModel = Cuadrillero::firstOrCreate(
                        ['nombres' => $nombres]
                    );
                    $cuadrilleroId = $cuadrilleroModel->id;
                    $nombres = $cuadrilleroModel->nombres;
                }

                // Guardamos el id en la lista de nuevos
                $idsNuevos[] = $cuadrilleroId;

                // 1. Asignamos a una variable para obtener el ID de la relación
                $tramoCuadrillero = CuadTramoLaboralCuadrillero::updateOrCreate(
                    [
                        'cuadrillero_id' => $cuadrilleroId,
                        'cuad_tramo_laboral_grupo_id' => $grupo->id,
                    ],
                    [
                        'nombres' => $nombres,
                        'orden' => $orden,
                    ]
                );

                // 2. Vinculamos/Actualizamos los registros diarios existentes con este tramo_cuadrillero_id
                CuadRegistroDiario::where('cuadrillero_id', $cuadrilleroId)
                    ->where('tramo_laboral_id', $this->tramoLaboral->id)
                    ->where('codigo_grupo', $this->codigo_grupo)
                    ->update(['tramo_cuadrillero_id' => $tramoCuadrillero->id]);
            }

            // 🔥 Eliminar diferenciales (los que ya estaban pero no están en los nuevos)
            // Gracias al ON DELETE CASCADE en la BD, eliminar aquí eliminará automáticamente
            // los registros de asistencia en 'cuad_registros_diarios' vinculados a este tramo_cuadrillero_id.
            CuadTramoLaboralCuadrillero::where('cuad_tramo_laboral_grupo_id', $grupo->id)
                ->whereNotIn('cuadrillero_id', $idsNuevos)
                ->delete();

            DB::commit();

            $this->alert('success', "Registros agregados");
            $this->mostrarAgregarCuadrillero = false;
            $this->resetForm();
            $this->dispatch('cuadrillerosAgregadosEnTramo');

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->alert('error', $th->getMessage());
        }
    }
    /*
    public function agregarListaAgregada()
    {
        try {

            if (!$this->codigo_grupo) {
                throw new Exception("No ha elegido ningún grupo");
            }

            // IDs de cuadrilleros que se registrarán en esta ejecución
            $idsNuevos = [];

            //verificar si existe el grupo en el tramoLaboral
            $grupo = $this->registrarGrupoEnTramoLaboral($this->tramoLaboral, $this->codigo_grupo);



            $orden = 0;
            foreach ($this->cuadrillerosAgregados as $cuadrillero) {
                $nombres = trim($cuadrillero['nombres'] ?? '');
                $cuadrilleroId = $cuadrillero['id'] ?? null;
                $orden++;

                if (!$cuadrilleroId) {
                    // Buscar cuadrillero por nombre o crear si no existe
                    $cuadrilleroModel = Cuadrillero::firstOrCreate(
                        ['nombres' => $nombres]
                    );
                    $cuadrilleroId = $cuadrilleroModel->id;
                    $nombres = $cuadrilleroModel->nombres;
                }

                // Guardamos el id en la lista de nuevos
                $idsNuevos[] = $cuadrilleroId;

                CuadTramoLaboralCuadrillero::updateOrCreate(
                    [
                        'cuadrillero_id' => $cuadrilleroId,
                        'cuad_tramo_laboral_grupo_id' => $grupo->id,

                    ],
                    [
                        'nombres' => $nombres,
                        'orden' => $orden,
                    ]
                );
            }

            // 🔥 Eliminar diferenciales (los que ya estaban pero no están en los nuevos)
            CuadTramoLaboralCuadrillero::where('cuad_tramo_laboral_grupo_id', $grupo->id)
                ->whereNotIn('cuadrillero_id', $idsNuevos)
                ->delete();

            $this->alert('success', "Registros agregados");
            $this->mostrarAgregarCuadrillero = false;
            $this->resetForm();
            $this->dispatch('cuadrillerosAgregadosEnTramo');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }*/
    public static function registrarGrupoEnTramoLaboral($tramoLaboral, $codigo)
    {
        // Verificar si ya existe el registro para esa fecha y grupo
        $grupo = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $tramoLaboral->id)
            ->where('codigo_grupo', $codigo)->first();

        if ($grupo) {
            return $grupo;
        }

        // 2. VALIDACIÓN DE SOLAPAMIENTO: 
        // Verificar si el grupo ya está asignado a OTRO tramo laboral que se traslape en fechas
        $grupoEnTramoSolapado = CuadTramoLaboralGrupo::where('codigo_grupo', $codigo)
            ->where('cuad_tramo_laboral_id', '!=', $tramoLaboral->id)
            ->whereHas('tramoLaboral', function ($query) use ($tramoLaboral) {
                $query->where('fecha_inicio', '<=', $tramoLaboral->fecha_fin)
                    ->where('fecha_fin', '>=', $tramoLaboral->fecha_inicio);
            })
            ->exists();

        if ($grupoEnTramoSolapado) {
            throw new Exception("El grupo '$codigo' no se puede agregar porque ya pertenece a otro tramo laboral cuyas fechas se cruzan con este.");
        }

        $grupo = CuaGrupo::where('codigo', $codigo)->first();
        if (!$grupo) {
            throw new Exception("El grupo con código $codigo no existe");
        }
        $costoDiaSugerido = $grupo->costo_dia_sugerido;

        // Obtener el orden máximo existente para esa fecha
        $maxOrden = CuadTramoLaboralGrupo::where('cuad_tramo_laboral_id', $tramoLaboral->id)->max('orden');

        // Asignar nuevo orden (max + 1)
        $nuevoOrden = is_null($maxOrden) ? 1 : $maxOrden + 1;

        // Registrar nuevo orden del grupo
        $nuevoGrupo = CuadTramoLaboralGrupo::create([
            'cuad_tramo_laboral_id' => $tramoLaboral->id,
            'codigo_grupo' => $codigo,
            'orden' => $nuevoOrden,
        ]);
        // 🚀 Si tiene costo sugerido, distribuirlo en la semana
        if ($costoDiaSugerido && $costoDiaSugerido > 0) {

            $fechaInicio = Carbon::parse($tramoLaboral->fecha_inicio);
            $fechaFin = Carbon::parse($tramoLaboral->fecha_fin);

            $datos = [
                [
                    'codigo_grupo' => $codigo,
                ]
            ];

            $i = 1;
            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay(), $i++) {
                $datos[0]["dia_$i"] = $costoDiaSugerido;
            }
            CuadrilleroServicio::guardarCostosDiariosGrupo($datos, $fechaInicio->toDateString());
        }

        return $nuevoGrupo;
    }
    public function agregarCuadrillerosEnTramo($codigo_grupo = null)
    {
        $this->resetForm();

        if ($codigo_grupo) {
            $this->codigo_grupo = $codigo_grupo;
        }

        $this->obtenerCuadrillerosAgregados();
        $this->mostrarAgregarCuadrillero = true;
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramo-agregar-cuadrillero-component');
    }
}
