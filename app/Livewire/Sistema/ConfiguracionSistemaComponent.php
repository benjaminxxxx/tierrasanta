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
    use LivewireAlert, \App\Traits\HandlesAlerts;

    /** @var array<int, mixed> días de la 1ª, 2ª… evaluación */
    public array $diasBrotes = [];
    public $diasMaximo;
    public $mesesReutilizar;
    public $mesesGrupoSinTramo;
    public $diasRecuperacion;
    public $factorSeco;
    /** @var array<int,int> meses proyectados por etapa de campaña (1ª sin infestación, 2ª, 3ª) */
    public array $mesesEtapas = [];
    /** @var array<string, bool> tipo de ingreso de cochinilla => vendible */
    public array $vendibles = [];
    /** @var array<int, mixed> días después de la infestación de la 1ª, 2ª… evaluación de infestación */
    public array $diasInfestacion = [];
    public bool $avisarInfestacionCerradas = false;
    /** Datos de la empresa (encabezados de reportes y Excel) */
    public array $empresa = ['ruc' => '', 'razon_social' => '', 'establecimiento' => ''];

    public function mount(): void
    {
        $this->empresa = array_map(fn($v) => (string) $v, \App\Services\Sistema\Empresa\SistemaEmpresaProceso::datos());
        $this->diasBrotes = CampaniaEtapaReglas::diasEvaluacionBrotes();
        $this->diasMaximo = CampaniaEtapaReglas::diasMaximoSugerencia();
        $this->diasInfestacion = CampaniaEtapaReglas::diasEvaluacionInfestacion();
        $this->avisarInfestacionCerradas = CampaniaEtapaReglas::avisarInfestacionEnCerradas();
        $this->mesesReutilizar = \App\Services\Campo\Labor\CampoLaborVigenciaConsulta::mesesParaReutilizar();
        $this->mesesGrupoSinTramo = \App\Services\Cuadrilla\Grupo\CuadrillaGrupoReglas::mesesSinTramo();
        $this->diasRecuperacion = \App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas::diasAlerta();
        $this->factorSeco = \App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas::factorSeco();
        $this->mesesEtapas = \App\Services\Campania\InformeMensual\CampaniaInformeMensualReglas::mesesProyectadosEtapas();
        $this->vendibles = \App\Models\CochinillaObservacion::orderBy('descripcion')->pluck('es_vendible', 'codigo')->map(fn($v) => (bool) $v)->all();
    }

    public function guardarEmpresa(): void
    {
        try {
            app(\App\Services\Sistema\Empresa\SistemaEmpresaProceso::class)
                ->guardar($this->empresa['ruc'], $this->empresa['razon_social'], $this->empresa['establecimiento']);
            $this->empresa = array_map(fn($v) => (string) $v, \App\Services\Sistema\Empresa\SistemaEmpresaProceso::datos());
            $this->alert('success', 'Datos de la empresa guardados. Los reportes y Excel nuevos ya los usan.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
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
            $this->errorAlert($e);
        }
    }

    public function agregarEvaluacionInfestacion(): void
    {
        $ultimo = (int) (end($this->diasInfestacion) ?: 45);
        $this->diasInfestacion[] = $ultimo + 15;
    }

    public function quitarEvaluacionInfestacion(int $i): void
    {
        unset($this->diasInfestacion[$i]);
        $this->diasInfestacion = array_values($this->diasInfestacion);
    }

    public function guardarInfestacion(): void
    {
        CampaniaEtapaReglas::guardarInfestacion($this->diasInfestacion, $this->avisarInfestacionCerradas);
        $this->diasInfestacion = CampaniaEtapaReglas::diasEvaluacionInfestacion();
        $this->resetErrorBag();
        $this->alert('success', 'Configuración guardada. Los avisos se actualizan al revisar las tareas pendientes.');
    }

    public function guardarLabores(): void
    {
        \App\Services\Campo\Labor\CampoLaborVigenciaConsulta::guardarMesesParaReutilizar($this->mesesReutilizar);
        $this->resetErrorBag();
        $this->alert('success', 'Configuración de labores guardada.');
    }

    public function guardarMesesEtapas(): void
    {
        \App\Services\Campania\InformeMensual\CampaniaInformeMensualReglas::guardarMesesProyectadosEtapas($this->mesesEtapas);
        $this->resetErrorBag();
        $this->alert('success', 'Meses proyectados guardados. Los informes nuevos o recalculados ya los usan.');
    }

    public function guardarRecuperacion(): void
    {
        \App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas::guardarDiasAlerta($this->diasRecuperacion);
        \App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas::guardarFactorSeco($this->factorSeco);
        $this->resetErrorBag();
        $this->alert('success', 'Recuperación de cochinilla: configuración guardada.');
    }

    public function guardarGruposCuadrilla(): void
    {
        \App\Services\Cuadrilla\Grupo\CuadrillaGrupoReglas::guardarMesesSinTramo($this->mesesGrupoSinTramo);
        $this->resetErrorBag();
        $this->alert('success', 'Configuración de grupos de cuadrilla guardada.');
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
