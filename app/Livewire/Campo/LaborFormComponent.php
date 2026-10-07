<?php

namespace App\Livewire\Campo;

use App\Constants\Permisos;
use App\Models\Labores;
use App\Models\ManoObra;
use App\Models\PlanTipoAsistencia;
use App\Services\Campo\Labor\LaborServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "Registro de labores": crear o editar una labor. Se abre con los eventos crearLabor / editarLabor y,
 * al guardar, avisa con laborGuardada para que la lista se actualice.
 */
class LaborFormComponent extends Component
{
    use LivewireAlert;

    public bool $mostrar = false;
    public $laborId;
    /** Motivo por el que el código no se puede cambiar (tiene registros o historia); null = editable */
    public ?string $codigoFijo = null;
    public $codigo;
    public $nombre_labor;
    public $estandar_produccion;
    public $unidades;
    public $codigo_mano_obra;
    public bool $se_paga_con_jornal = false;
    public $tipo_asistencia_codigo;
    public array $tramos = [['hasta' => '', 'monto' => '']];

    #[On('crearLabor')]
    public function crear(): void
    {
        $this->soloGestion();
        $this->limpiar();
        $this->mostrar = true;
    }

    #[On('editarLabor')]
    public function editar(int $id): void
    {
        $this->soloGestion();
        $this->limpiar();
        $labor = Labores::find($id);
        if (!$labor) {
            $this->alert('error', 'Labor no encontrada.');
            return;
        }
        $this->laborId = $labor->id;
        $this->codigo = $labor->codigo;
        $this->codigoFijo = LaborServicio::motivoCodigoFijo($labor);
        $this->nombre_labor = $labor->nombre_labor;
        $this->estandar_produccion = $labor->estandar_produccion;
        $this->unidades = $labor->unidades;
        $this->codigo_mano_obra = $labor->codigo_mano_obra;
        $this->se_paga_con_jornal = (bool) $labor->se_paga_con_jornal;
        $this->tipo_asistencia_codigo = $labor->tipo_asistencia_codigo;
        // La columna guarda el JSON como texto (el cast del modelo solo quita una capa)
        $tramos = is_string($labor->tramos_bonificacion) ? json_decode($labor->tramos_bonificacion, true) : $labor->tramos_bonificacion;
        $this->tramos = is_array($tramos) && $tramos ? $tramos : [['hasta' => '', 'monto' => '']];
        $this->mostrar = true;
    }

    public function guardar(): void
    {
        $this->soloGestion();
        try {
            LaborServicio::guardar([
                'codigo' => $this->codigo,
                'nombre_labor' => $this->nombre_labor,
                'estandar_produccion' => $this->estandar_produccion,
                'unidades' => $this->unidades,
                'tramos_bonificacion' => empty($this->tramos) ? null : json_encode($this->tramos),
                'codigo_mano_obra' => $this->codigo_mano_obra,
                'se_paga_con_jornal' => $this->se_paga_con_jornal,
                'tipo_asistencia_codigo' => $this->tipo_asistencia_codigo,
            ], $this->laborId);

            $this->limpiar();
            $this->mostrar = false;
            $this->alert('success', 'Labor guardada correctamente.');
            $this->dispatch('laborGuardada');
        } catch (ValidationException $ve) {
            // Además del error bajo cada campo, un aviso: un error en un campo que no lo muestra cortaba el guardado sin decir nada
            $this->alert('error', 'No se guardó la labor: ' . implode(' ', $ve->validator->errors()->all()), ['timer' => 6000]);
            throw $ve;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    private function limpiar(): void
    {
        $this->resetErrorBag();
        $this->reset(['laborId', 'codigoFijo', 'codigo', 'nombre_labor', 'codigo_mano_obra', 'estandar_produccion', 'unidades', 'tramos',
            'tipo_asistencia_codigo', 'se_paga_con_jornal']);
    }

    private function soloGestion(): void
    {
        abort_unless(auth()->user()?->can(Permisos::CAMPO_LABOR_GESTIONAR), 403, 'No tienes permiso para registrar labores.');
    }

    public function render()
    {
        return view('livewire.campo.labor-form-component', [
            // Solo con el modal abierto: la página carga sin consultas de más
            'manoObras' => $this->mostrar ? ManoObra::orderBy('descripcion')->get(['codigo', 'descripcion']) : collect(),
            // Asistencias que una labor puede representar (labores de suspensión: DM, V, FR…)
            'tiposAsistencia' => $this->mostrar
                ? PlanTipoAsistencia::where('codigo', '<>', 'A')->orderBy('codigo')->get(['codigo', 'descripcion'])
                : collect(),
        ]);
    }
}
