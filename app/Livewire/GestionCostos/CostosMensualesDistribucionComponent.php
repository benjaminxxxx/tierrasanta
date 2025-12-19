<?php

namespace App\Livewire\GestionCostos;
use App\Models\CostoMensual;
use App\Models\CostoMensualDistribucion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CostosMensualesDistribucionComponent extends Component
{
    use LivewireAlert;
    public $mostrarDistribucionesMensuales = false;
    public $mes;
    public $anio;
    public $distribuciones = [];
    public $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    protected $listeners = ['verDistribucionCostosMensuales'];
    public function verDistribucionCostosMensuales(int $costoMensualId): void
    {
        try {
            $costoMensual = CostoMensual::findOrFail($costoMensualId);
            $this->mes = $costoMensual->mes;
            $this->anio = $costoMensual->anio;
            $this->distribuciones = CostoMensualDistribucion::query()
                ->with('campania:id,nombre_campania,fecha_inicio,fecha_fin')
                ->where('costo_mensual_id', $costoMensualId)
                ->get()
                ->map(function ($d) {
                    $bloques = [
                        'administrativo' => (float) $d->fijo_administrativo,
                        'financiero' => (float) $d->fijo_financiero,
                        'oficina' => (float) $d->fijo_gastos_oficina,
                        'depreciaciones' => (float) $d->fijo_depreciaciones,
                        'terreno' => (float) $d->fijo_costo_terreno,
                        'servicios' => (float) $d->operativo_servicios_fundo,
                        'mano_obra' => (float) $d->operativo_mano_obra_indirecta,
                    ];

                    return [
                        'id' => $d->campo_campania_id,
                        'nombre' => $d->campania->nombre_campania,
                        'fecha_inicio' => $d->campania->fecha_inicio,
                        'fecha_fin' => $d->campania->fecha_fin,
                        'dias_activos' => $d->dias_activos,

                        'bloques' => $bloques,
                        'total' => array_sum($bloques),
                    ];
                })
                ->toArray();

            $this->mostrarDistribucionesMensuales = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-costos.costos-mensuales-distribucion-component');
    }
}