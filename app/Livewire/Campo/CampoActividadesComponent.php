<?php

namespace App\Livewire\Campo;

use App\Services\Campo\Actividad\CampoActividadConsulta;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Actividades por campo (DESIGN.md): qué códigos de labor se hicieron en cada campo y día, cuántos trabajadores de
 * planilla y de cuadrilla y cuántas horas, y qué combinaciones se aceptaron al registrar (campo_actividades_aceptadas).
 */
#[Title('Actividades por campo')]
class CampoActividadesComponent extends Component
{
    /** Más días a la vez vuelve la página pesada */
    public const DIAS_MAXIMO = 62;

    #[Url]
    public ?string $desde = null;
    #[Url]
    public ?string $hasta = null;
    #[Url]
    public ?string $campo = null;
    /** Solo los campos-día con más de un código */
    #[Url]
    public bool $soloVarias = false;

    public function mount(): void
    {
        $this->hasta ??= now()->toDateString();
        $this->desde ??= now()->subDays(6)->toDateString();
    }

    public function render()
    {
        $hasta = $this->hasta ?: now()->toDateString();
        $desde = $this->desde ?: Carbon::parse($hasta)->subDays(6)->toDateString();
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $recortado = Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) >= self::DIAS_MAXIMO;
        if ($recortado) {
            $desde = Carbon::parse($hasta)->subDays(self::DIAS_MAXIMO - 1)->toDateString();
        }

        $filas = collect(app(CampoActividadConsulta::class)->historial($desde, $hasta, $this->campo ?: null))
            ->when($this->soloVarias, fn($c) => $c->filter(fn($d) => count($d['actividades']) > 1))
            ->groupBy('fecha')->sortKeysDesc();

        return view('livewire.campo.campo-actividades-component', [
            'dias' => $filas,
            'recortado' => $recortado,
            'desdeEfectivo' => $desde,
        ]);
    }
}
