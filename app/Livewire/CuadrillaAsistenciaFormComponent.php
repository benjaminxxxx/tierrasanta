<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CuadrillaAsistenciaFormComponent extends Component
{
    use LivewireAlert;
    public $isFormOpen = false;
    public $gruposCuadrilla;
    public $grupos = [];
    public $fecha_inicio;
    public $fecha_fin;
    public $titulo;
    public $semanaId;
    protected $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre',
    ];
    protected $listeners = ['editarSemana'];

    public function mount()
    {
    }
    public function editarSemana($semanaId)
    {
        $this->semanaId = $semanaId;
        $semana = CuaAsistenciaSemanal::find($this->semanaId);
        if (!$semana) {
            return $this->alert("error", "La semana ya no existe.");
        }
        $this->fecha_inicio = $semana->fecha_inicio;
        $this->fecha_fin = $semana->fecha_fin;
        $this->titulo = $semana->titulo;
        $this->gruposCuadrilla = CuaGrupo::where('estado', true)->get();

        if ($this->gruposCuadrilla) {

            $gruposActuales = $semana->grupos()->get()->pluck('gru_cua_cod')->toArray();

            foreach ($this->gruposCuadrilla as $grupoCuadrilla) {

                $grupo = $semana->grupos()->where('gru_cua_cod', $grupoCuadrilla->codigo)->first();
                if (!$grupo) {
                    continue;
                }
                $this->grupos[$grupoCuadrilla->codigo] = [
                    'activo' => in_array($grupoCuadrilla->codigo, $gruposActuales),  // Si no hay registro anterior, por defecto se activa
                    'costo_dia_sugerido' => $grupo->costo_dia,
                    'total' => $grupo->costo_dia / 8,
                ];
            }
        }

        $this->isFormOpen = true;
    }
    public function CrearRegistroSemanal()
    {
        $this->fecha_inicio = null;
        $this->fecha_fin = null;
        $this->titulo = null;

        $this->gruposCuadrilla = CuaGrupo::where('estado', true)->get();

        $CuaAsistenciaSemanalAnterior = CuaAsistenciaSemanal::orderBy('fecha_inicio', 'desc')->first();

        if ($CuaAsistenciaSemanalAnterior) {
            // Obtener grupos de CuaAsistenciaSemanalGrupo con 'activo' en true

            $gruposAnteriores = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $CuaAsistenciaSemanalAnterior->id)
                ->get()->pluck('gru_cua_cod')->toArray();

            if ($this->gruposCuadrilla) {
                foreach ($this->gruposCuadrilla as $grupoCuadrilla) {
                    $this->grupos[$grupoCuadrilla->codigo] = [
                        'activo' => in_array($grupoCuadrilla->codigo, $gruposAnteriores),  // Si no hay registro anterior, por defecto se activa
                        'costo_dia_sugerido' => $grupoCuadrilla->costo_dia_sugerido,
                        'total' => $grupoCuadrilla->costo_dia_sugerido / 8,
                    ];
                }
            }
        } else {
            if ($this->gruposCuadrilla) {
                foreach ($this->gruposCuadrilla as $grupoCuadrilla) {
                    $this->grupos[$grupoCuadrilla->codigo] = [
                        'activo' => true,  // Si no hay registro anterior, por defecto se activa
                        'costo_dia_sugerido' => $grupoCuadrilla->costo_dia_sugerido,
                        'total' => $grupoCuadrilla->costo_dia_sugerido / 8,
                    ];
                }
            }
        }

        $this->isFormOpen = true;
    }
    public function render()
    {
        return view('livewire.cuadrilla-asistencia-form-component');
    }
    public function store()
    {
        $this->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'titulo' => 'required|string|max:255',
        ]);

        $conflicto = CuaAsistenciaSemanal::where(function ($query) {
            $query->whereBetween('fecha_inicio', [$this->fecha_inicio, $this->fecha_fin])
                ->orWhereBetween('fecha_fin', [$this->fecha_inicio, $this->fecha_fin])
                ->orWhere(function ($query) {
                    $query->where('fecha_inicio', '<=', $this->fecha_inicio)
                        ->where('fecha_fin', '>=', $this->fecha_fin);
                });
        })
        ->whereNot('id',$this->semanaId)
        ->exists();

        if ($conflicto) {
            return $this->alert('error', 'El rango de fechas seleccionado entra en conflicto con otro registro existente.');
        }

        DB::beginTransaction();

        try {
            $CuaAsistenciaSemanal = null;
            // Insertar en CuaAsistenciaSemanal
            if($this->semanaId){
                $CuaAsistenciaSemanal  = CuaAsistenciaSemanal::find($this->semanaId);

                //codigo de modificacion 20250122CORRECCION_AL_EDITAR_SEMANA_CUADRILLA
                $actividades =  $CuaAsistenciaSemanal->actividades
                ->where('fecha','<',$this->fecha_inicio)
                ->orWhere('fecha','>',$this->fecha_fin)
                ->get();

                if($actividades->count()>0){
                    foreach ($actividades as $actividad) {
                        $actividad->recogidas()->delete();
                        $actividad->cuadrillero_actividades()->delete();
                    }
                }
                /************************20250122CORRECCION_AL_EDITAR_SEMANA_CUADRILLA */

                if($CuaAsistenciaSemanal){
                    $CuaAsistenciaSemanal->update([
                        'titulo' => $this->titulo,
                        'fecha_inicio' => $this->fecha_inicio,
                        'fecha_fin' => $this->fecha_fin
                    ]);
                    /************************20250122CORRECCION_AL_EDITAR_SEMANA_CUADRILLA */
                    $CuaAsistenciaSemanal->actualizarTotales();
                    $CuaAsistenciaSemanal->contabilizarHoras();
                    /************************20250122CORRECCION_AL_EDITAR_SEMANA_CUADRILLA */
                }
                
            }else{
                $CuaAsistenciaSemanal = CuaAsistenciaSemanal::create([
                    'titulo' => $this->titulo,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin,
                    'estado' => 'pendiente'
                ]);
            }
            
            if(!$CuaAsistenciaSemanal){
                return;
            }

            // Obtener el ID insertado
            $CuaAsistenciaSemanalId = $CuaAsistenciaSemanal->id;

            $CuaAsistenciaSemanalAnterior = CuaAsistenciaSemanal::where('fecha_fin', '<', $this->fecha_inicio)
                ->orderBy('fecha_fin', 'desc')
                ->first();

            foreach ($this->gruposCuadrilla as $grupo) {
                if (array_key_exists($grupo->codigo, $this->grupos) && $this->grupos[$grupo->codigo]['activo'] && (float) $this->grupos[$grupo->codigo]['costo_dia_sugerido'] > 0) {
                    
                    if($this->semanaId){
                        $grupoExistente = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id',$CuaAsistenciaSemanalId)
                        ->where('gru_cua_cod',$grupo->codigo)
                        ->first();

                        if($grupoExistente){
                            $grupoExistente->update([
                                'costo_dia' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'],
                                'costo_hora' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'] / 8
                            ]);
                        }else{
                            $nuevoGrupo = CuaAsistenciaSemanalGrupo::create([
                                'cua_asi_sem_id' => $CuaAsistenciaSemanalId,
                                'gru_cua_cod' => $grupo->codigo,
                                'costo_dia' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'],
                                'costo_hora' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'] / 8,
                                'total_costo' => 0
                            ]);
                        }
                        
                    }else{
                        $nuevoGrupo = CuaAsistenciaSemanalGrupo::create([
                            'cua_asi_sem_id' => $CuaAsistenciaSemanalId,
                            'gru_cua_cod' => $grupo->codigo,
                            'costo_dia' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'],
                            'costo_hora' => $this->grupos[$grupo->codigo]['costo_dia_sugerido'] / 8,
                            'total_costo' => 0
                        ]);
    
                        // Si hay una semana anterior, buscar el grupo equivalente y copiar cuadrilleros
                        if ($CuaAsistenciaSemanalAnterior) {
    
                            $grupoAnterior = CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $CuaAsistenciaSemanalAnterior->id)
                                ->where('gru_cua_cod', $grupo->codigo)
                                ->first();
    
                            // Si el grupo existía en la semana anterior, copiar sus cuadrilleros al nuevo grupo
                            $conpersonales = false;
                            if(array_key_exists('conpersonales',$this->grupos[$grupo->codigo])){
                                $conpersonales = $this->grupos[$grupo->codigo]['conpersonales'];
                            }
                            if ($grupoAnterior && $conpersonales) {
                                $cuadrillerosAnteriores = CuaAsistenciaSemanalCuadrillero::where('cua_asi_sem_gru_id', $grupoAnterior->id)->get();
    
                                foreach ($cuadrillerosAnteriores as $cuadrilleroAnterior) {
                                    CuaAsistenciaSemanalCuadrillero::create([
                                        'cua_id' => $cuadrilleroAnterior->cua_id,
                                        'cua_asi_sem_gru_id' => $nuevoGrupo->id,
                                        'monto_recaudado' => 0
                                    ]);
                                }
                            }
                        }
                    }
                    
                }else{
                    //este codigo es para que al momento de editar, si deselecciono un grupo, este sea eliminado
                    CuaAsistenciaSemanalGrupo::where('cua_asi_sem_id', $CuaAsistenciaSemanalId)
                            ->where('gru_cua_cod', $grupo->codigo)
                            ->delete();
                }
            }

            // Commit de la transacción
            DB::commit();

            // Mostrar mensaje de éxito
            $this->alert('success', 'La cuadrilla se guardó correctamente.');
            $this->closeForm(); // Si quieres cerrar el formulario
            $this->dispatch('NuevaCuadrilla');
            if($this->semanaId){
                $this->dispatch('reiniciarTablaCuadrillero');
            }
            
        } catch (QueryException $e) {
            // Rollback de la transacción en caso de error
            DB::rollBack();
            $this->alert('error', 'Hubo un problema al guardar la cuadrilla. Error: ' . $e->getMessage());
        }
    }
    public function evaluarTituloFecha()
    {
        // Verificar si solo fecha_inicio tiene valor
        if ($this->fecha_inicio && !$this->fecha_fin) {
            $inicio = Carbon::parse($this->fecha_inicio);
            $this->fecha_fin = $inicio->copy()->addDays(5)->format('Y-m-d');
        }

        // Verificar si solo fecha_fin tiene valor
        if (!$this->fecha_inicio && $this->fecha_fin) {
            $fin = Carbon::parse($this->fecha_fin);
            $this->fecha_inicio = $fin->copy()->subDays(5)->format('Y-m-d');
        }

        if ($this->fecha_inicio && $this->fecha_fin) {
            $inicio = Carbon::parse($this->fecha_inicio);
            $fin = Carbon::parse($this->fecha_fin);

            if ($fin->greaterThanOrEqualTo($inicio)) {
                $mesInicio = $this->meses[$inicio->format('F')];
                $mesFin = $this->meses[$fin->format('F')];

                $this->titulo = 'CUADRILLA MENSUAL DEL ' . $inicio->format('d') . ' de ' . $mesInicio . ' AL ' . $fin->format('d') . ' de ' . $mesFin;
            } else {
                $this->titulo = '';
            }
        } else {
            $this->titulo = '';
        }
    }

    public function updated($propertyName)
    {
        if (str_contains($propertyName, 'grupos') && str_contains($propertyName, 'costo_dia_sugerido')) {
            $codigo = explode('.', $propertyName)[1];

            // Recalcula `total` solo si `costo_dia_sugerido` cambió
            if (isset($this->grupos[$codigo]['costo_dia_sugerido'])) {
                $this->grupos[$codigo]['total'] = $this->grupos[$codigo]['costo_dia_sugerido'] / 8;
            }
        }
    }
    public function closeForm()
    {
        $this->isFormOpen = false;
    }
}
