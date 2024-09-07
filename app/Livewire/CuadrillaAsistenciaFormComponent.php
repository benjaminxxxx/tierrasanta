<?php

namespace App\Livewire;

use App\Models\CuadrillaAsistencia;
use App\Models\CuadrillaAsistenciaCuadrillero;
use App\Models\CuadrillaAsistenciaGrupo;
use App\Models\Cuadrillero;
use App\Models\GruposCuadrilla;
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
    public $costo_dia = [];
    public $fecha_inicio;
    public $fecha_fin;
    public $titulo;
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
    public function mount()
    {

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

        $conflicto = CuadrillaAsistencia::where(function ($query) {
            $query->whereBetween('fecha_inicio', [$this->fecha_inicio, $this->fecha_fin])
                ->orWhereBetween('fecha_fin', [$this->fecha_inicio, $this->fecha_fin])
                ->orWhere(function ($query) {
                    $query->where('fecha_inicio', '<=', $this->fecha_inicio)
                        ->where('fecha_fin', '>=', $this->fecha_fin);
                });
        })->exists();

        if ($conflicto) {
            $this->alert('error', 'El rango de fechas seleccionado entra en conflicto con otro registro existente.');
            return;
        }

        DB::beginTransaction();

        try {
            // Insertar en CuadrillaAsistencia
            $cuadrillaAsistencia = CuadrillaAsistencia::create([
                'titulo' => $this->titulo,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'estado' => 'pendiente'
                // 'total' y 'estado' no se guardan por ahora
            ]);

            // Obtener el ID insertado
            $cuadrillaAsistenciaId = $cuadrillaAsistencia->id;

            CuadrillaAsistenciaGrupo::where('cuadrilla_asistencia_id', $cuadrillaAsistenciaId)->delete();

            // Recorrer los grupos y guardar en CuadrillaAsistenciaGrupo
            foreach ($this->gruposCuadrilla as $grupo) {
                CuadrillaAsistenciaGrupo::create([
                    'cuadrilla_asistencia_id' => $cuadrillaAsistenciaId,
                    'codigo' => $grupo->codigo,
                    'color' => $grupo->color,
                    'nombre' => $grupo->nombre,
                    'modalidad_pago' => $grupo->modalidad_pago,
                    'costo_dia' => $this->costo_dia[$grupo->codigo] ?? $grupo->costo_dia_sugerido,
                ]);
            }

            // Importar cuadrilleros y asociarlos a la cuadrilla
            CuadrillaAsistenciaCuadrillero::where('cuadrilla_asistencia_id', $cuadrillaAsistenciaId)->delete();
            $cuadrilleros = Cuadrillero::all();
            foreach ($cuadrilleros as $cuadrillero) {
                CuadrillaAsistenciaCuadrillero::create([
                    'cuadrilla_asistencia_id' => $cuadrillaAsistenciaId,
                    'nombres' => $cuadrillero->nombre_completo,
                    'identificador' => $cuadrillero->codigo,
                    'dni' => $cuadrillero->dni,
                    'codigo_grupo' => $cuadrillero->codigo_grupo,
                    'monto_recaudado' => 0,
                    'planilla' => null,
                ]);
            }

            // Commit de la transacción
            DB::commit();

            // Mostrar mensaje de éxito
            $this->alert('success', 'La cuadrilla se guardó correctamente.');
            $this->closeForm(); // Si quieres cerrar el formulario
            $this->dispatch('NuevaCuadrilla');

        } catch (QueryException $e) {
            // Rollback de la transacción en caso de error
            DB::rollBack();

            // Manejar el error y mostrar un mensaje
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
    public function CrearRegistroSemanal()
    {
        $this->gruposCuadrilla = GruposCuadrilla::all();
        if ($this->gruposCuadrilla) {
            $this->costo_dia = $this->gruposCuadrilla->pluck('costo_dia_sugerido', 'codigo')->toArray();
        }
        $this->isFormOpen = true;
    }
    public function closeForm()
    {
        $this->isFormOpen = false;
    }
}
