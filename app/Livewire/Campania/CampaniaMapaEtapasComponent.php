<?php

namespace App\Livewire\Campania;

use App\Constants\Permisos;
use App\Models\CampoCampania;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use App\Services\Campania\Sector\CampaniaSectorProceso;
use App\Services\Campania\Sector\CampaniaSectorReglas;
use Illuminate\Support\Carbon;
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
        $this->cargar($this->campania());
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
        $this->sectores[] = ['nombre' => CampaniaSectorReglas::nombreSiembra(count($this->sectores) + 1), 'fecha_siembra' => null,
            'hilera_desde' => $desde, 'hilera_hasta' => $hasta, 'origen' => 'manual', 'etapas' => $etapas];
        $this->sectores = collect($this->sectores)->sortBy('hilera_desde')->values()->all();
    }

    public function quitarGrupo(int $i): void
    {
        unset($this->sectores[$i]);
        $this->sectores = array_values($this->sectores);
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

    public function guardar(): void
    {
        try {
            abort_unless($this->puedeEditar(), 403);
            $c = $this->campania();
            app(CampaniaSectorProceso::class)->guardar($c, (int) $this->hileras, $this->sectores);
            $this->editando = false;
            $this->cargar($c);
            $this->alert('success', 'Mapa de etapas guardado.');
            $this->dispatch('campaniaInsertada');
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
        ]);
    }
}
