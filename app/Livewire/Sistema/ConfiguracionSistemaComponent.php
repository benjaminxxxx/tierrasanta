<?php

namespace App\Livewire\Sistema;

use App\Services\Campania\Etapa\CampaniaEtapaReglas;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Sistema → Configuración: parámetros de negocio que no son reglas fijas. Por ahora, los días promedio de cada
 * evaluación de brotes desde el inicio de la campaña (con ellos se sugieren evaluaciones en tareas pendientes).
 */
#[Title('Configuración')]
class ConfiguracionSistemaComponent extends Component
{
    use LivewireAlert;

    /** @var array<int, mixed> días de la 1ª, 2ª… evaluación */
    public array $diasBrotes = [];
    public $diasMaximo;
    public $mesesReutilizar;
    /** @var array<string, bool> tipo de ingreso de cochinilla => vendible */
    public array $vendibles = [];

    public function mount(): void
    {
        $this->diasBrotes = CampaniaEtapaReglas::diasEvaluacionBrotes();
        $this->diasMaximo = CampaniaEtapaReglas::diasMaximoSugerencia();
        $this->mesesReutilizar = \App\Services\Campo\Labor\CampoLaborVigenciaConsulta::mesesParaReutilizar();
        $this->vendibles = \App\Models\CochinillaObservacion::orderBy('descripcion')->pluck('es_vendible', 'codigo')->map(fn($v) => (bool) $v)->all();
    }

    public function agregarEvaluacion(): void
    {
        $ultimo = (int) (end($this->diasBrotes) ?: 20);
        $this->diasBrotes[] = $ultimo + 15;
    }

    public function quitarEvaluacion(int $i): void
    {
        unset($this->diasBrotes[$i]);
        $this->diasBrotes = array_values($this->diasBrotes);
    }

    public function guardarBrotes(): void
    {
        try {
            CampaniaEtapaReglas::guardar($this->diasBrotes, $this->diasMaximo);
            $this->diasBrotes = CampaniaEtapaReglas::diasEvaluacionBrotes();
            $this->resetErrorBag();
            $this->alert('success', 'Configuración guardada. Las sugerencias se actualizan al revisar las tareas pendientes.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function guardarLabores(): void
    {
        \App\Services\Campo\Labor\CampoLaborVigenciaConsulta::guardarMesesParaReutilizar($this->mesesReutilizar);
        $this->resetErrorBag();
        $this->alert('success', 'Configuración de labores guardada.');
    }

    /** Qué tipos de ingreso de cochinilla se pueden vender (la mamá que va a infestar no: vuelve como infestadores). */
    public function guardarVendibles(): void
    {
        foreach ($this->vendibles as $codigo => $vendible) {
            \App\Models\CochinillaObservacion::whereKey($codigo)->update(['es_vendible' => (bool) $vendible]);
        }
        \App\Services\Reporte\AuditoriaServicio::registrar(\App\Models\CochinillaObservacion::class, 0, 'editar', null,
            ['vendibles' => $this->vendibles], 'Tipos de ingreso de cochinilla vendibles');
        $this->alert('success', 'Tipos de ingreso vendibles guardados.');
    }

    public function render()
    {
        return view('livewire.sistema.configuracion-sistema-component', [
            'tiposCochinilla' => \App\Models\CochinillaObservacion::orderBy('descripcion')->pluck('descripcion', 'codigo'),
        ]);
    }
}
