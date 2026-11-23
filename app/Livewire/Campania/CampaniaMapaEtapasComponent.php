<?php

namespace App\Livewire\Campania;

use App\Constants\Permisos;
use App\Exceptions\ErrorControlado;
use App\Exceptions\SiembraConEvaluaciones;
use App\Models\CampoCampania;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use App\Services\Campania\Sector\CampaniaSectorProceso;
use App\Services\Campania\Sector\CampaniaSectorReglas;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal "Mapa de etapas" (DESIGN.md): el campo dibujado con sus hileras, en qué etapa está cada grupo de hileras en
 * una fecha y la línea de tiempo de cada grupo. Se abre con el evento `verMapaEtapas` (campaniaId) desde el menú de
 * opciones de la campaña. Quien gestiona campañas puede corregir hileras, grupos y fechas de etapa
 * (CampaniaSectorProceso: no deja labores registradas fuera de su etapa).
 */
class CampaniaMapaEtapasComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public bool $mostrar = false;
    public ?int $campaniaId = null;
    public bool $editando = false;
    public int $hileras = 10;
    /** [['nombre','hilera_desde','hilera_hasta','origen','etapas' => [['etapa','fecha_desde'], …]], …] */
    public array $sectores = [];
    /** Fecha en que se ve el mapa */
    public ?string $fecha = null;

    /** Aviso al quitar una siembra con evaluaciones */
    public bool $avisoEvaluaciones = false;
    /** Clave de la siembra que se quiere quitar (null = lo pidió el guardado); el índice cambia si se agrega otra */
    public ?string $siembraPorQuitar = null;
    public array $evaluacionesVinculadas = [];
    /** clave de la evaluación => ['accion' => mover|eliminar, 'destino' => clave de la siembra] */
    public array $decisiones = [];
    /** Lo ya decidido, se aplica al guardar el mapa */
    public array $decisionesConfirmadas = [];
    public ?string $fechaSiembraDestino = null;
    /** La fecha de una siembra nueva ya existe como plantación: se confirma antes de guardar */
    public ?string $siembraExistenteMensaje = null;

    #[On('verMapaEtapas')]
    public function verMapaEtapas(int $campaniaId): void
    {
        $c = CampoCampania::find($campaniaId);
        if (!$c || !$c->fecha_inicio) {
            $this->errorAlert('La campaña no existe o no tiene fecha de inicio.');
            return;
        }
        $this->campaniaId = $c->id;
        $this->editando = false;
        $this->cargar($c);
        $this->fecha = CampaniaSectorConsulta::fin($c);
        $this->mostrar = true;
    }

    private function cargar(CampoCampania $c): void
    {
        $this->resetErrorBag();
        $this->hileras = CampaniaSectorConsulta::hilerasDelCampo($c->campo);
        $this->sectores = app(CampaniaSectorConsulta::class)->sectores($c);
        if (!$this->sectores) {
            // Campaña sin detectar todavía (nueva): se arma con lo registrado
            app(CampaniaSectorProceso::class)->detectar($c);
            $this->sectores = app(CampaniaSectorConsulta::class)->sectores($c);
        }
    }

    private function campania(): CampoCampania
    {
        return CampoCampania::findOrFail($this->campaniaId);
    }

    private function puedeEditar(): bool
    {
        return (bool) auth()->user()?->canAny([Permisos::CAMPAÑA_GESTIONAR, Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR]);
    }

    public function editar(): void
    {
        abort_unless($this->puedeEditar(), 403);
        $this->editando = true;
    }

    public function cancelar(): void
    {
        $this->editando = false;
        $this->limpiarAviso();
        $this->decisionesConfirmadas = [];
        $this->cargar($this->campania());
    }

    private function limpiarAviso(): void
    {
        $this->reset(['avisoEvaluaciones', 'siembraPorQuitar', 'evaluacionesVinculadas', 'decisiones', 'fechaSiembraDestino']);
    }

    /** Al cambiar el número de hileras, los grupos se estiran o encogen en proporción. */
    public function updatedHileras($valor): void
    {
        $nuevo = max(1, min(500, (int) $valor));
        $anterior = max(1, (int) collect($this->sectores)->max('hilera_hasta'));
        $rangos = CampaniaSectorReglas::repartirHileras($nuevo, count($this->sectores));
        $proporcional = [];
        foreach ($this->sectores as $i => $s) {
            $desde = (int) round(((int) $s['hilera_desde'] - 1) / $anterior * $nuevo) + 1;
            $hasta = (int) round((int) $s['hilera_hasta'] / $anterior * $nuevo);
            $proporcional[$i] = $hasta >= $desde ? [$desde, $hasta] : $rangos[$i] ?? [1, 1];
        }
        foreach ($this->sectores as $i => &$s) {
            [$s['hilera_desde'], $s['hilera_hasta']] = $proporcional[$i];
        }
        unset($s);
        $this->hileras = $nuevo;
    }

    /** Nueva siembra (relleno): con las hileras libres, o partiendo en dos la siembra más grande. Necesita su fecha de siembra. */
    public function agregarGrupo(): void
    {
        $ocupadas = [];
        foreach ($this->sectores as $s) {
            for ($h = (int) $s['hilera_desde']; $h <= (int) $s['hilera_hasta']; $h++) {
                $ocupadas[$h] = true;
            }
        }
        $libres = array_values(array_diff(range(1, $this->hileras), array_keys($ocupadas)));
        $etapas = $this->sectores[count($this->sectores) - 1]['etapas'] ?? [];
        if ($libres) {
            $desde = $libres[0];
            $hasta = $desde;
            while (in_array($hasta + 1, $libres, true)) {
                $hasta++;
            }
        } else {
            $i = collect($this->sectores)->sortByDesc(fn($s) => $s['hilera_hasta'] - $s['hilera_desde'])->keys()->first();
            $s = $this->sectores[$i];
            if ($s['hilera_hasta'] === $s['hilera_desde']) {
                $this->errorAlert('Ya no hay hileras para otra siembra: aumenta el número de hileras del campo.');
                return;
            }
            $mitad = intdiv($s['hilera_desde'] + $s['hilera_hasta'], 2);
            $this->sectores[$i]['hilera_hasta'] = $mitad;
            $desde = $mitad + 1;
            $hasta = $s['hilera_hasta'];
            $etapas = $s['etapas'];
        }
        $this->sectores[] = ['clave' => 'n' . Str::random(10), 'nombre' => CampaniaSectorReglas::nombreSiembra(count($this->sectores) + 1), 'fecha_siembra' => null,
            'hilera_desde' => $desde, 'hilera_hasta' => $hasta, 'origen' => 'manual', 'etapas' => $etapas];
        $this->sectores = collect($this->sectores)->sortBy('hilera_desde')->values()->all();
    }

    /** Candado: si la siembra tiene evaluaciones, primero se decide qué pasa con ellas. */
    public function quitarGrupo(int $i): void
    {
        try {
            $id = $this->sectores[$i]['id'] ?? null;
            $c = $this->campania();
            $vinculadas = $id ? app(EvaluacionSiembraConsulta::class)->evaluaciones($c, [(int) $id]) : [];
            if ($vinculadas) {
                EvaluacionSiembraConsulta::exigirAbierta($c);
                $this->abrirAviso($vinculadas, $this->claveDe($this->sectores[$i]));
                return;
            }
            $this->sacarSiembra($i);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function sacarSiembra(int $i): void
    {
        $clave = $this->claveDe($this->sectores[$i] ?? []);
        unset($this->sectores[$i]);
        $this->sectores = array_values($this->sectores);
        // Lo que iba a pasar a esta siembra (aún sin guardar) vuelve a preguntarse al guardar
        $this->decisionesConfirmadas = array_filter($this->decisionesConfirmadas,
            fn($d) => $d['accion'] !== 'mover' || (string) $d['destino'] !== $clave);
    }

    /** Identifica a la siembra en el mapa aunque cambien sus hileras o su fecha antes de guardar. */
    private function claveDe(array $s): string
    {
        return (string) ($s['clave'] ?? (!empty($s['id']) ? "s{$s['id']}" : ''));
    }

    private function abrirAviso(array $vinculadas, ?string $clave): void
    {
        $this->siembraPorQuitar = $clave;
        $this->evaluacionesVinculadas = $vinculadas;
        $this->fechaSiembraDestino = null;
        $destinos = $this->destinos();
        $this->decisiones = [];
        foreach ($vinculadas as $e) {
            $sugerida = EvaluacionSiembraConsulta::sugerida($e['fecha'], $destinos);
            $this->decisiones[$e['clave']] = $this->decisionesConfirmadas[$e['clave']]
                ?? ['accion' => 'mover', 'destino' => $sugerida['clave'] ?? ''];
        }
        $this->avisoEvaluaciones = true;
    }

    /** Siembras que quedan en el mapa y pueden recibir las evaluaciones. */
    private function destinos(): array
    {
        $quitadas = array_unique(array_column($this->evaluacionesVinculadas, 'sector_id'));
        return collect($this->sectores)
            ->reject(fn($s) => !empty($s['id']) && in_array((int) $s['id'], $quitadas, false))
            ->map(fn($s) => ['clave' => $this->claveDe($s), 'nombre' => $s['nombre'], 'fecha_siembra' => $s['fecha_siembra'] ?? null])
            ->values()->all();
    }

    /** "Corregir con una siembra nueva": se agrega al mapa y queda como destino de las evaluaciones sin destino. */
    public function agregarSiembraDestino(): void
    {
        try {
            $fecha = trim((string) $this->fechaSiembraDestino);
            if ($fecha === '') {
                throw new ErrorControlado('Elige la fecha de la siembra nueva.');
            }
            $fecha = Carbon::parse($fecha)->toDateString();
            if (collect($this->sectores)->contains(fn($s) => ($s['fecha_siembra'] ?? null) === $fecha)) {
                throw new ErrorControlado('Ya hay una siembra con esa fecha: elígela en la lista.');
            }
            $antes = array_map(fn($s) => $this->claveDe($s), $this->sectores);
            $this->agregarGrupo();
            $i = collect($this->sectores)->search(fn($s) => !in_array($this->claveDe($s), $antes, true));
            if ($i === false) {
                return; // sin hileras: agregarGrupo ya avisó
            }
            $inicio = Carbon::parse($this->campania()->fecha_inicio)->toDateString();
            $this->sectores[$i]['fecha_siembra'] = $fecha;
            $this->sectores[$i]['etapas'] = [['etapa' => 'siembra', 'fecha_desde' => max($fecha, $inicio)]];
            $nueva = $this->claveDe($this->sectores[$i]);
            foreach ($this->decisiones as $clave => $d) {
                if ($d['accion'] === 'mover' && !$d['destino']) {
                    $this->decisiones[$clave]['destino'] = $nueva;
                }
            }
            $this->fechaSiembraDestino = null;
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function cancelarAviso(): void
    {
        $this->limpiarAviso();
    }

    /** Guarda lo decidido; se aplica junto con el mapa, al presionar Guardar. */
    public function confirmarAviso(): void
    {
        try {
            $claves = array_column($this->destinos(), 'clave');
            foreach ($this->evaluacionesVinculadas as $e) {
                $d = $this->decisiones[$e['clave']] ?? [];
                if (($d['accion'] ?? '') === 'mover' && !in_array((string) ($d['destino'] ?? ''), $claves, true)) {
                    throw new ErrorControlado("Elige a qué siembra pasa la evaluación de {$e['nombre_tipo']} del " . Carbon::parse($e['fecha'])->format('d/m/Y') . ', o márcala para eliminar.');
                }
            }
            $this->decisionesConfirmadas = array_merge($this->decisionesConfirmadas, $this->decisiones);
            if ($this->siembraPorQuitar !== null) {
                $i = collect($this->sectores)->search(fn($s) => $this->claveDe($s) === $this->siembraPorQuitar);
                if ($i !== false) {
                    $this->sacarSiembra($i);
                }
            }
            $this->limpiarAviso();
            $this->alert('info', 'Presiona Guardar para aplicar los cambios.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function agregarEtapa(int $i): void
    {
        $this->sectores[$i]['etapas'][] = ['etapa' => '', 'fecha_desde' => $this->fecha];
    }

    public function quitarEtapa(int $i, int $k): void
    {
        unset($this->sectores[$i]['etapas'][$k]);
        $this->sectores[$i]['etapas'] = array_values($this->sectores[$i]['etapas']);
    }

    public function noEsSiembraExistente(): void
    {
        $this->siembraExistenteMensaje = null;
    }

    /** $unir: la persona confirmó que la plantación ya registrada en esa fecha es esta siembra. */
    public function guardar(bool $unir = false): void
    {
        try {
            abort_unless($this->puedeEditar(), 403);
            $c = $this->campania();
            $unir = $unir && $this->siembraExistenteMensaje !== null;
            $this->siembraExistenteMensaje = null;
            app(CampaniaSectorProceso::class)->guardar($c, (int) $this->hileras, $this->sectores, null, 'mapa', $this->decisionesConfirmadas, $unir);
            $this->editando = false;
            $this->decisionesConfirmadas = [];
            $this->cargar($c);
            $this->alert('success', 'Mapa de etapas guardado.');
            $this->dispatch('campaniaInsertada');
        } catch (SiembraConEvaluaciones $e) {
            $this->abrirAviso($e->evaluaciones, null);
        } catch (\App\Exceptions\SiembraYaRegistrada $e) {
            $this->siembraExistenteMensaje = $e->getMessage();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    /** Vuelve a armar los grupos con lo registrado (descarta lo corregido a mano). */
    public function redetectar(): void
    {
        try {
            abort_unless($this->puedeEditar(), 403);
            $c = $this->campania();
            app(CampaniaSectorProceso::class)->detectar($c, true);
            $this->editando = false;
            $this->cargar($c);
            $this->alert('success', 'Se volvió a detectar con las labores registradas.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $c = $this->campaniaId ? CampoCampania::find($this->campaniaId) : null;
        $dibujo = null;
        $resumenLabores = [];
        $fuera = collect();
        if ($c && $this->mostrar) {
            $consulta = app(CampaniaSectorConsulta::class);
            $fecha = $this->fecha ?: CampaniaSectorConsulta::fin($c);
            $sectores = collect($this->sectores)->map(fn($s) => $s + ['etapas' => []])->map(function ($s) {
                $s['etapas'] = array_values(array_filter($s['etapas'], fn($e) => !empty($e['etapa']) && !empty($e['fecha_desde'])));
                return $s;
            })->all();
            $dibujo = $consulta->dibujo($c, max(1, (int) $this->hileras), $sectores, $fecha);
            $registros = $consulta->registros($c);
            $resumenLabores = $registros->groupBy('etapa')->map(fn($g, $etapa) => [
                'etapa' => $etapa,
                'dias' => $g->pluck('fecha')->unique()->count(),
                'desde' => $g->min('fecha'),
                'hasta' => $g->max('fecha'),
            ])->sortBy('desde')->values()->all();
            $fuera = $consulta->fueraDeEtapa($sectores, $registros);
        }
        return view('livewire.campania.campania-mapa-etapas-component', [
            'campania' => $c,
            'dibujo' => $dibujo,
            'resumenLabores' => $resumenLabores,
            'fuera' => $fuera,
            'etapas' => CampaniaSectorReglas::ETAPAS,
            'puedeEditar' => $this->puedeEditar(),
            'destinosEvaluacion' => $this->avisoEvaluaciones ? $this->destinos() : [],
        ]);
    }
}
