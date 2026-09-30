<?php

namespace App\Livewire\Campania;

use App\Models\CampoCampania;
use App\Services\Campania\Cobertura\CampaniaCoberturaConsulta;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use App\Services\Campania\Registro\CampaniaRegistroConsulta;
use App\Services\Campania\Registro\CampaniaRegistroImpactoConsulta;
use App\Services\Campania\Registro\CampaniaRegistroProceso;
use App\Services\Campania\Registro\CampaniaRegistroValidador;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * Ficha de campaña: registro (wizard) y edición (pestañas verticales), como el panel de empleados.
 *
 * Registro (evento `registroCampania`, opcional campoNombre):
 *   1. Campo: últimas campañas; si hay una vigente hay que cerrarla antes (modal CampaniaCerrarComponent).
 *   2. Fechas: inicio (y cierre si es una campaña pasada). No puede solaparse ni dejar días con actividades
 *      sin campaña entre la anterior y esta.
 *   3. Datos: área (la del campo, editable), nombre sugerido, variedad. Al registrar se pasa a modo edición.
 *
 * Edición (evento `editarCampania`: campaniaId, tab): datos generales, fechas (mismas validaciones + impacto y
 * confirmación), infestación, reinfestación, cosecha de madres y cosecha.
 */
class CampaniaFichaComponent extends Component
{
    use LivewireAlert;

    public const TABS = [
        'general' => 'Datos generales',
        'fechas' => 'Fechas',
        'infestacion' => 'Infestación',
        'reinfestacion' => 'Reinfestación',
        'cosecha-madres' => 'Cosecha de madres',
        'cosecha' => 'Cosecha',
    ];

    public const PASOS = [1 => 'Campo', 2 => 'Fechas', 3 => 'Datos'];

    public bool $mostrar = false;
    public ?int $campaniaId = null;
    public string $tab = 'general';
    public int $paso = 1;

    // Registro
    public ?string $campo = null;
    public ?array $contexto = null;

    // Fechas (registro paso 2 y pestaña "Fechas")
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?array $huecos = null;
    public ?array $avisosFechas = null;

    // Datos de la campaña (paso 3 y pestañas de edición)
    public array $campania = [];
    public ?array $resumen = null;

    protected $listeners = ['registroCampania', 'editarCampania', 'campaniaCerrada'];

    // ------------------------------------------------------------------ Registro

    public function registroCampania(?string $campoNombre = null): void
    {
        $this->reiniciar();
        $this->mostrar = true;
        if ($campoNombre) {
            $this->campo = $campoNombre;
            $this->cargarContexto();
        }
    }

    public function updatedCampo(): void
    {
        $this->cargarContexto();
    }

    public function elegirOtroCampo(): void
    {
        $this->reset(['campo', 'contexto']);
    }

    public function abrirCierreVigente(): void
    {
        if ($id = $this->contexto['vigente']['id'] ?? null) {
            $this->dispatch('cerrarCampania', campaniaId: $id);
        }
    }

    /** El modal de cierre avisa al terminar: si estamos en el paso 1, se recarga el campo. */
    public function campaniaCerrada(): void
    {
        if (!$this->campaniaId && $this->campo) {
            $this->cargarContexto();
        } elseif ($this->campaniaId) {
            $this->cargarCampania($this->campaniaId);
        }
    }

    /** "Ya cerré la campaña, continuar" (si se cerró en otra pestaña). */
    public function yaCerre(): void
    {
        $this->cargarContexto();
        if ($this->contexto['vigente'] ?? null) {
            $this->alert('warning', "La campaña {$this->contexto['vigente']['nombre']} sigue abierta.");
            return;
        }
        $this->irAFechas();
    }

    public function irAFechas(): void
    {
        if (!$this->contexto || $this->contexto['vigente']) {
            return;
        }
        $this->paso = 2;
        $this->fechaInicio ??= $this->contexto['sugerido']['fecha_inicio'];
        $this->huecos = null;
    }

    public function volver(): void
    {
        $this->resetErrorBag();
        $this->paso = max(1, $this->paso - 1);
    }

    /** Paso 2 → 3: sin solapamiento y sin días con actividades fuera de toda campaña. */
    public function revisarFechasNuevas(): void
    {
        $this->resetErrorBag();
        $this->validate(
            ['fechaInicio' => 'required|date', 'fechaFin' => 'nullable|date|after_or_equal:fechaInicio'],
            ['fechaInicio.required' => 'Indica la fecha de inicio.', 'fechaFin.after_or_equal' => 'El cierre debe ser igual o posterior al inicio.'],
        );

        if (!$this->fechasSinConflictos($this->campo, null)) {
            return;
        }

        $this->paso = 3;
        $this->campania['area'] ??= $this->contexto['area'];
        $this->campania['nombre_campania'] ??= $this->contexto['sugerido']['nombre_campania'];
        $this->campania['variedad_tuna'] ??= $this->contexto['sugerido']['variedad_tuna'];
    }

    public function registrar(): void
    {
        $this->validate([
            'campania.area' => 'required|numeric|between:0,99999999.99',
            'campania.nombre_campania' => 'required|string|max:255',
            'campania.variedad_tuna' => 'nullable|string|max:50',
        ], [
            'campania.area.required' => 'El área es obligatoria.',
            'campania.area.numeric' => 'El área debe ser un número.',
            'campania.nombre_campania.required' => 'El nombre de la campaña es obligatorio.',
        ]);

        try {
            $campania = app(CampaniaRegistroProceso::class)->crear([
                'campo' => $this->campo,
                'fecha_inicio' => $this->fechaInicio,
                'fecha_fin' => $this->fechaFin ?: null,
                'area' => $this->campania['area'],
                'nombre_campania' => $this->campania['nombre_campania'],
                'variedad_tuna' => $this->campania['variedad_tuna'] ?? null,
            ]);

            $this->alert('success', "Campaña {$campania->nombre_campania} registrada. Ya puedes completar infestación, cosecha, etc.");
            $this->dispatch('campaniaInsertada', $campania->toArray());
            $this->cargarCampania($campania->id);
            $this->tab = 'general';
        } catch (ValidationException $e) {
            // Si el problema es de fechas, volver al paso 2
            if (array_intersect(array_keys($e->errors()), ['campania.fecha_inicio', 'campania.fecha_fin'])) {
                $this->paso = 2;
            }
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    /** Arreglos rápidos del aviso de días sin campaña. */
    public function usarFechaInicio(string $fecha): void
    {
        $this->fechaInicio = $fecha;
        $this->huecos = null;
        $this->avisosFechas = null;
    }

    public function usarFechaFin(string $fecha): void
    {
        $this->fechaFin = $fecha;
        $this->huecos = null;
        $this->avisosFechas = null;
    }

    // ------------------------------------------------------------------ Edición

    public function editarCampania(int $campaniaId, ?string $tab = 'general'): void
    {
        $this->reiniciar();
        if (!CampoCampania::whereKey($campaniaId)->exists()) {
            $this->alert('error', 'La campaña ya no existe.');
            return;
        }
        $this->cargarCampania($campaniaId);
        $this->tab = array_key_exists($tab ?? '', self::TABS) ? $tab : 'general';
        $this->mostrar = true;
    }

    public function updatedTab(): void
    {
        $this->resetErrorBag();
        $this->avisosFechas = null;
        $this->huecos = null;
    }

    public function updatedFechaInicio(): void
    {
        $this->huecos = null;
        $this->avisosFechas = null;
    }

    public function updatedFechaFin(): void
    {
        $this->huecos = null;
        $this->avisosFechas = null;
    }

    public function guardarGeneral(): void
    {
        $this->validate([
            'campania.nombre_campania' => 'required|string|max:255',
            'campania.area' => 'required|numeric|between:0,99999999.99',
            'campania.variedad_tuna' => 'nullable|string|max:50',
            'campania.sistema_cultivo' => 'nullable|string|max:255',
            'campania.tipo_cambio' => 'nullable|numeric|between:0,99999999.99',
            'campania.pencas_x_hectarea' => 'nullable|numeric',
        ], [
            'campania.nombre_campania.required' => 'El nombre de la campaña es obligatorio.',
            'campania.area.required' => 'El área es obligatoria.',
            'campania.area.numeric' => 'El área debe ser un número.',
            'campania.tipo_cambio.numeric' => 'El tipo de cambio debe ser un número.',
            'campania.pencas_x_hectarea.numeric' => 'Las pencas por hectárea deben ser un número.',
        ]);
        $this->guardarDatos(['nombre_campania', 'area', 'variedad_tuna', 'sistema_cultivo', 'tipo_cambio', 'pencas_x_hectarea']);
    }

    /** Pestañas de detalle (infestación, reinfestación, cosecha…): guarda todo lo editable menos fechas y campo. */
    public function guardarDetalle(): void
    {
        $this->guardarDatos(null);
    }

    /** Pestaña "Fechas": valida y muestra el impacto antes de confirmar. */
    public function revisarCambioFechas(): void
    {
        $this->resetErrorBag();
        $this->validate(
            ['fechaInicio' => 'required|date', 'fechaFin' => 'nullable|date|after_or_equal:fechaInicio'],
            ['fechaInicio.required' => 'Indica la fecha de inicio.', 'fechaFin.after_or_equal' => 'El cierre debe ser igual o posterior al inicio.'],
        );

        $campania = CampoCampania::findOrFail($this->campaniaId);
        if (!$this->fechasSinConflictos($campania->campo, $campania->id)) {
            return;
        }

        $analisis = app(CampaniaRegistroImpactoConsulta::class)->analizar($campania, $this->fechaInicio, $this->fechaFin ?: null);
        if (!$analisis['hay_cambios']) {
            $this->alert('info', 'Las fechas no cambiaron.');
            return;
        }
        $this->avisosFechas = $analisis['avisos'] ?: ['No hay costos ni registros afectados por el cambio.'];
    }

    public function confirmarCambioFechas(): void
    {
        if ($this->avisosFechas === null) {
            return;
        }
        try {
            $campania = app(CampaniaRegistroProceso::class)->cambiarFechas($this->campaniaId, $this->fechaInicio, $this->fechaFin ?: null);
            $this->alert('success', 'Fechas actualizadas.');
            $this->dispatch('campaniaInsertada', $campania->toArray());
            $this->cargarCampania($campania->id);
        } catch (ValidationException $e) {
            $this->avisosFechas = null;
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function abrirCierre(): void
    {
        if ($this->campaniaId) {
            $this->dispatch('cerrarCampania', campaniaId: $this->campaniaId);
        }
    }

    // ------------------------------------------------------------------ Internos

    /**
     * Solapamiento (error) y huecos con actividades (aviso con arreglos rápidos). Devuelve true si se puede seguir.
     */
    private function fechasSinConflictos(string $campo, ?int $campaniaId): bool
    {
        $fin = $this->fechaFin ?: null;
        $choques = app(CampaniaRegistroValidador::class)
            ->solapamientos($campo, $this->fechaInicio, $fin, array_filter([$campaniaId]));
        if ($choques->isNotEmpty()) {
            $this->addError('fechaInicio', 'Las fechas se cruzan con: ' . $choques
                ->map(fn($c) => "{$c->nombre_campania} (" . formatear_fecha($c->fecha_inicio) . ' – '
                    . ($c->fecha_fin ? formatear_fecha($c->fecha_fin) : 'abierta') . ')')
                ->implode(', ') . '.');
            return false;
        }

        $huecos = app(CampaniaCoberturaConsulta::class)->huecos($campo, $this->fechaInicio, $fin, $campaniaId);
        $this->huecos = null;
        foreach ($huecos as $lado => $hueco) {
            if ($hueco && $hueco['actividades']) {
                $this->huecos[$lado] = [
                    'desde' => $hueco['desde'],
                    'hasta' => $hueco['hasta'],
                    'vecina' => $hueco['vecina']->nombre_campania,
                    'actividades' => $hueco['actividades'],
                    'primer_dia' => array_key_first($hueco['actividades']),
                    'ultimo_dia' => array_key_last($hueco['actividades']),
                ];
            }
        }
        return $this->huecos === null;
    }

    private function guardarDatos(?array $soloCampos): void
    {
        try {
            $data = $soloCampos ? array_intersect_key($this->campania, array_flip($soloCampos)) : $this->campania;
            $campania = app(CampaniaRegistroProceso::class)->actualizarDatos($this->campaniaId, $data);
            $this->alert('success', 'Cambios guardados.');
            $this->dispatch('campaniaInsertada', $campania->toArray());
            $this->cargarCampania($campania->id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    private function cargarContexto(): void
    {
        $this->resetErrorBag();
        $this->contexto = $this->campo ? app(CampaniaRegistroConsulta::class)->contextoCampo($this->campo) : null;
        $this->paso = 1;
        $this->fechaInicio = null;
        $this->fechaFin = null;
        $this->campania = [];
    }

    private function cargarCampania(int $campaniaId): void
    {
        $campania = CampoCampania::with('campo_model')->findOrFail($campaniaId);
        $estado = app(CampaniaCosechaConsulta::class)->estado($campania);

        $this->campaniaId = $campania->id;
        $this->campo = $campania->campo;
        $this->campania = $campania->toArray();
        $this->campania['fecha_inicio'] = $campania->fecha_inicio->toDateString();
        $this->campania['fecha_fin'] = $campania->fecha_fin?->toDateString();
        if (blank($this->campania['area'] ?? null)) {
            // Campañas antiguas sin área: se propone la del campo
            $this->campania['area'] = $campania->campo_model?->area;
        }
        $this->fechaInicio = $this->campania['fecha_inicio'];
        $this->fechaFin = $this->campania['fecha_fin'];
        $this->huecos = null;
        $this->avisosFechas = null;
        $this->resumen = [
            'nombre' => $campania->nombre_campania,
            'campo' => $campania->campo,
            'area_campo' => $campania->campo_model?->area,
            'inicio' => $this->campania['fecha_inicio'],
            'fin' => $this->campania['fecha_fin'],
            'estado' => $estado,
        ];
    }

    private function reiniciar(): void
    {
        $this->resetErrorBag();
        $this->reset(['campaniaId', 'paso', 'campo', 'contexto', 'fechaInicio', 'fechaFin', 'huecos', 'avisosFechas', 'campania', 'resumen']);
        $this->tab = 'general';
    }

    public function render()
    {
        return view('livewire.campania.campania-ficha-component');
    }
}
