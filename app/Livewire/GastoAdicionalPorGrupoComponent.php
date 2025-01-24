<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GastoAdicionalPorGrupoComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $gastos;
    public $grupoId;
    public $grupo;
    public $descripcion;
    public $monto;
    public $aniosContablesPermitidos;
    public $mesContable;
    public $anioContable;
    public $listeners = ['verDetalleGastosAdicionalesPorGrupo'];

    public function verDetalleGastosAdicionalesPorGrupo($grupoId)
    {
        try {
            $this->resetForm();
            $this->grupoId = $grupoId;
            $this->grupo = CuaAsistenciaSemanalGrupo::findOrFail($grupoId);
            $this->listarGastosAdicionales();
            $this->aniosContablesPermitidos = $this->cargarAnioContablePermitido();

            $asistenciaSemanal = $this->grupo->asistenciaSemanal;
            $fechaInicio = Carbon::parse($asistenciaSemanal->fecha_inicio);

            $this->anioContable = (int)$fechaInicio->year;
            $this->mesContable = (int)$fechaInicio->month;

            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al ver los detalles Gastos Adicionales.');
        }
    }
    public function cargarAnioContablePermitido()
    {
        if (!$this->grupo) {
            throw new Exception("No se ha proporcionado un Id válido");
        }

        $asistenciaSemanal = $this->grupo->asistenciaSemanal;
        $fechaInicio = Carbon::parse($asistenciaSemanal->fecha_inicio);
        $fechaFin = Carbon::parse($asistenciaSemanal->fecha_fin);

        // Calcular los años "desde" y "hasta"
        $anioDesde = $fechaInicio->addMonth(-1)->year;
        $anioHasta = $fechaFin->addMonth(1)->year;



        // Crear el array de años contables permitidos
        $aniosContables = [];

        // Verificar si los años desde y hasta son iguales
        if ($anioDesde == $anioHasta) {
            $aniosContables[] = $anioDesde;
        } else {
            // Si no son iguales, agregar el rango de años
            for ($anio = $anioDesde; $anio <= $anioHasta; $anio++) {
                $aniosContables[] = $anio;
            }
        }

        return $aniosContables;
    }
    public function listarGastosAdicionales()
    {
        if (!$this->grupo) {
            throw new Exception("No se ha proporcionado un Id válido");
        }
        $this->gastos = GastoAdicionalPorGrupoCuadrilla::where('cua_asistencia_semanal_grupo_id', $this->grupoId)->get();
        $this->dispatch('cuadrillerosAgregadosAsistencia');
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['descripcion', 'monto', 'grupoId', 'grupo', 'gastos', 'anioContable', 'mesContable']);
    }

    public function eliminarGasto($GastoAdicionalPorGrupoCuadrillaId)
    {

        try {
            GastoAdicionalPorGrupoCuadrilla::findOrFail($GastoAdicionalPorGrupoCuadrillaId)->delete();
            $this->listarGastosAdicionales();
            $this->alert('success', 'Registro eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error inesperado.');
        }
    }
    public function storeRegistrarGastoAdicional()
    {
        $this->validate([
            'descripcion' => 'required|string|max:255',  // Descripción obligatoria, tipo cadena, máximo 255 caracteres
            'monto' => 'required|numeric|min:0.01',      // Monto obligatorio, debe ser un número, mínimo 0.01
            'mesContable' => 'required|integer|min:1|max:12', // Mes contable obligatorio, debe ser entre 1 y 12
            'anioContable' => 'required|integer|min:1900|max:2100', // Año contable obligatorio, entre 1900 y 2100
        ], [
            // Mensajes personalizados
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',

            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número válido.',
            'monto.min' => 'El monto debe ser mayor que 0.',

            'mesContable.required' => 'El mes contable es obligatorio.',
            'mesContable.integer' => 'El mes contable debe ser un número entero.',
            'mesContable.min' => 'El mes contable debe ser entre 1 y 12.',
            'mesContable.max' => 'El mes contable debe ser entre 1 y 12.',

            'anioContable.required' => 'El año contable es obligatorio.',
            'anioContable.integer' => 'El año contable debe ser un número entero.',
            'anioContable.min' => 'El año contable debe ser un valor válido.',
            'anioContable.max' => 'El año contable debe ser un valor válido.',
        ]);

        try {
            if (!$this->grupoId) {
                throw new Exception("No se ha proporcionado un Id");
            }
            GastoAdicionalPorGrupoCuadrilla::create([
                'descripcion' => $this->descripcion,
                'monto' => $this->monto,
                'cua_asistencia_semanal_grupo_id' => $this->grupoId,
                'mes_contable' => $this->mesContable,
                'anio_contable' => $this->anioContable,
            ]);
            $this->listarGastosAdicionales();
            $this->alert('success', 'Registro agregado correctamente.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error inesperado.');
        }
    }
    public function render()
    {
        return view('livewire.gasto-adicional-por-grupo-component');
    }
}
