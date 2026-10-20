<?php

namespace App\Livewire\Costos;

use App\Constants\Permisos;
use App\Models\CostoMensual;
use App\Services\Costos\ManoObraIndirecta\CostosManoObraIndirectaConsulta;
use App\Services\Costos\ManoObraIndirecta\CostosManoObraIndirectaProceso;
use App\Traits\HandlesAlerts;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

/**
 * /costos/mano-obra-indirecta: de dónde sale la mano de obra indirecta del mes (todo lo gastado en FDM), explicada por
 * sección y grupo, en blanco y negro. "Calcular" pone al día la BDD y los kardex, guarda el resultado en el costo
 * mensual (reemplaza el valor manual) y genera el Excel. Comparte el mes con /costos/mensual (misma sesión).
 */
class CostosManoObraIndirectaComponent extends Component
{
    use ConSelectorMes, LivewireAlert, HandlesAlerts;

    public function mount(): void
    {
        // Enlace desde /costos/mensual con ?anio=&mes=
        if (request()->integer('anio') && request()->integer('mes')) {
            Session::put($this->dateSessionKey, ['mes' => request()->integer('mes'), 'anio' => request()->integer('anio')]);
        }
        $this->inicializarMesAnio();
    }

    protected function despuesMesAnioModificado(string $anio, string $mes)
    {
    }

    public function calcular(): void
    {
        try {
            abort_unless(auth()->user()->can(Permisos::CONTABILIDAD_COSTO_MENSUAL_GESTIONAR), 403);
            $r = app(CostosManoObraIndirectaProceso::class)->calcular((int) $this->anio, (int) $this->mes);
            $this->alert($r['concluyente'] ? 'success' : 'warning', 'Mano de obra indirecta guardada: S/ ' . number_format($r['total'], 2)
                . ($r['concluyente'] ? '.' : '. No es concluyente: revisa los avisos y vuelve a calcular cuando estén resueltos.'), ['timer' => 6000]);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function descargar()
    {
        $costo = CostoMensual::where('anio', $this->anio)->where('mes', $this->mes)->first();
        if (!$costo?->moi_archivo || !Storage::disk('public')->exists($costo->moi_archivo)) {
            $this->errorAlert('Todavía no hay Excel: calcula el mes primero.');
            return null;
        }
        return Storage::disk('public')->download($costo->moi_archivo);
    }

    public function render()
    {
        $calculo = null;
        try {
            $calculo = app(CostosManoObraIndirectaConsulta::class)->calcular((int) $this->anio, (int) $this->mes);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
        return view('livewire.costos.costos-mano-obra-indirecta-component', [
            'calculo' => $calculo,
            'guardado' => CostoMensual::with('moiCalculadoPor:id,name')->where('anio', $this->anio)->where('mes', $this->mes)->first(),
            'puedeCalcular' => auth()->user()->can(Permisos::CONTABILIDAD_COSTO_MENSUAL_GESTIONAR),
        ]);
    }
}
