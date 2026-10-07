<?php

namespace App\Livewire\Costos;

use App\Constants\Permisos;
use App\Models\ManoObra;
use App\Services\Campo\ManoObra\CampoManoObraCrud;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Mano de obra (grupos de labores para los costos de producción). Con labores, no se elimina ni cambia de código:
 * solo se corrige su descripción (ver CampoManoObraCrud).
 */
class ManoObraComponent extends Component
{
    use LivewireAlert;
    public $manoObraCodigo; // código de la que se edita (null = nueva)
    public $codigo;
    public $descripcion;
    /** Por qué el código no se puede cambiar (null = editable) */
    public ?string $codigoFijo = null;
    public $mostrarFormularioManoObra = false;
    protected $listeners = [
        'confirmarEliminarManoObra',
    ];

    private function crud(): CampoManoObraCrud
    {
        return app(CampoManoObraCrud::class);
    }

    private function soloGestion(): void
    {
        abort_unless(auth()->user()?->can(Permisos::CAMPO_MANO_OBRA_GESTIONAR), 403);
    }

    public function abrirFormManoObra($codigo = null)
    {
        $this->soloGestion();
        $this->reset('manoObraCodigo', 'codigo', 'descripcion', 'codigoFijo');
        $this->resetErrorBag();
        if ($codigo) {
            $manoObra = ManoObra::findOrFail($codigo);
            $this->manoObraCodigo = $manoObra->codigo;
            $this->codigo = $manoObra->codigo;
            $this->descripcion = $manoObra->descripcion;
            $this->codigoFijo = $this->crud()->motivoFijo($manoObra->codigo);
        }
        $this->mostrarFormularioManoObra = true;
    }

    public function guardarManoObra()
    {
        $this->soloGestion();
        try {
            $this->crud()->guardar($this->manoObraCodigo, ['codigo' => $this->codigo, 'descripcion' => $this->descripcion]);
            $this->alert('success', 'Datos guardados correctamente.');
            $this->mostrarFormularioManoObra = false;
            $this->reset('manoObraCodigo', 'codigo', 'descripcion', 'codigoFijo');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function eliminarManoObra($codigo)
    {
        $this->soloGestion();
        $manoObra = ManoObra::findOrFail($codigo);
        if ($motivo = $this->crud()->motivoFijo($manoObra->codigo)) {
            $this->alert('warning', "No se puede eliminar \"{$manoObra->descripcion}\": {$motivo}. Si ya no se usa, pasa antes sus labores a otra mano de obra.", [
                'position' => 'center', 'toast' => false, 'timer' => null, 'showConfirmButton' => true,
            ]);
            return;
        }
        $this->confirm("¿Eliminar la mano de obra \"{$manoObra->descripcion}\"? No tiene labores.", [
            'onConfirmed' => 'confirmarEliminarManoObra',
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function confirmarEliminarManoObra($data)
    {
        $this->soloGestion();
        try {
            $this->crud()->eliminar($data['codigo']);
            $this->alert('success', 'Mano de obra eliminada.');
        } catch (ValidationException $e) {
            $this->alert('error', implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.costos.mano-obra-component', [
            'manoObras' => ManoObra::orderBy('descripcion')->get(),
            'labores' => $this->crud()->laboresPorCodigo(),
        ]);
    }
}
