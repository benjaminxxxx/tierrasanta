<?php

namespace App\Livewire\Campo;

use App\Models\CampoCampania;
use App\Services\Campo\Actividad\CampoActividadCampaniaConsulta;
use App\Services\Campo\Actividad\CampoActividadConsulta;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Actividades por campo (DESIGN.md): qué códigos de labor se hicieron en cada campo y día, con su etapa, cuántos
 * trabajadores de planilla y de cuadrilla y cuántas horas.
 *  - Por día: todos los campos (o uno), día por día.
 *  - Por campaña: un campo, agrupado por campaña (encabezado vertical) y por grupo de hileras según la etapa de cada
 *    labor (encabezado horizontal, mapa de etapas). Al elegir una campaña se ponen solas sus fechas.
 */
#[Title('Actividades por campo')]
class CampoActividadesComponent extends Component
{
    /** Más días a la vez vuelve la página pesada (todos los campos) */
    public const DIAS_MAXIMO = 62;
    /** Por campaña es un solo campo: alcanza para campañas largas */
    public const DIAS_MAXIMO_CAMPANIA = 800;

    #[Url]
    public ?string $desde = null;
    #[Url]
    public ?string $hasta = null;
    #[Url]
    public ?string $campo = null;
    /** Solo los campos-día con más de un código */
    #[Url]
    public bool $soloVarias = false;
    /** dias | campania */
    #[Url]
    public string $vista = 'dias';
    /** Campaña elegida en la vista por campaña (pone sus fechas) */
    #[Url]
    public ?int $campania = null;

    public function mount(): void
    {
        $this->vista = in_array($this->vista, ['dias', 'campania'], true) ? $this->vista : 'dias';
        if ($this->vista === 'campania' && $this->campania && (!$this->desde || !$this->hasta)) {
            $this->updatedCampania($this->campania);
        }
        $this->hasta ??= now()->toDateString();
        $this->desde ??= now()->subDays(6)->toDateString();
    }

    public function updatedCampo(): void
    {
        // Otra campaña de otro campo ya no aplica: se toma la última del campo nuevo
        if ($this->vista === 'campania') {
            $this->campania = null;
            $ultima = $this->campo ? CampoCampania::where('campo', $this->campo)->whereNotNull('fecha_inicio')->orderByDesc('fecha_inicio')->value('id') : null;
            if ($ultima) {
                $this->campania = $ultima;
                $this->updatedCampania($ultima);
            }
        }
    }

    public function updatedVista(): void
    {
        if ($this->vista === 'campania' && $this->campo && !$this->campania) {
            $this->updatedCampo();
        }
    }

    public function updatedCampania($id): void
    {
        $c = $id ? CampoCampania::find($id) : null;
        if ($c && $c->fecha_inicio) {
            $this->campo = $c->campo;
            $this->desde = Carbon::parse($c->fecha_inicio)->toDateString();
            $this->hasta = $c->fecha_fin ? Carbon::parse($c->fecha_fin)->toDateString() : now()->toDateString();
        }
    }

    public function render()
    {
        $porCampania = $this->vista === 'campania';
        $maximo = $porCampania ? self::DIAS_MAXIMO_CAMPANIA : self::DIAS_MAXIMO;
        $hasta = $this->hasta ?: now()->toDateString();
        $desde = $this->desde ?: Carbon::parse($hasta)->subDays(6)->toDateString();
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $recortado = Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) >= $maximo;
        if ($recortado) {
            $desde = Carbon::parse($hasta)->subDays($maximo - 1)->toDateString();
        }

        $datos = ['recortado' => $recortado, 'desdeEfectivo' => $desde, 'maximo' => $maximo, 'dias' => collect(), 'bloques' => [], 'campanias' => collect()];
        if ($porCampania) {
            $datos['campanias'] = $this->campo
                ? CampoCampania::where('campo', $this->campo)->whereNotNull('fecha_inicio')->orderByDesc('fecha_inicio')->get(['id', 'nombre_campania', 'fecha_inicio', 'fecha_fin'])
                : collect();
            $datos['bloques'] = $this->campo ? app(CampoActividadCampaniaConsulta::class)->bloques($this->campo, $desde, $hasta) : [];
        } else {
            $datos['dias'] = collect(app(CampoActividadConsulta::class)->historial($desde, $hasta, $this->campo ?: null))
                ->when($this->soloVarias, fn($c) => $c->filter(fn($d) => count($d['actividades']) > 1))
                ->groupBy('fecha')->sortKeysDesc();
        }

        return view('livewire.campo.campo-actividades-component', $datos);
    }
}
