<?php

namespace App\Livewire\Costos;

use App\Constants\Permisos;
use App\Services\Campo\Bloqueo\CampoBloqueoProceso;
use App\Services\Costos\Mensual\CostosMensualConsulta;
use App\Services\Costos\Mensual\CostosMensualExcel;
use App\Services\Costos\Mensual\CostosMensualProceso;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Costos → Mensual: campañas que reciben costo en el mes (con tarjeta de cada campaña y cierre), costos fijos y
 * operativos en una tabla editable, distribución y Excel. La lógica está en App\Services\Costos\Mensual.
 */
class ContabilidadCostosMensualesDetalleComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public $mes;
    public $anio;

    /** Tarjeta de la campaña elegida (CostosMensualConsulta::tarjeta) */
    public ?array $tarjeta = null;
    public bool $mostrarTarjeta = false;
    public bool $verBloqueados = false;

    public function guardarCostos(array $filas): void
    {
        $this->autorizar();
        try {
            app(CostosMensualProceso::class)->guardar((int) $this->anio, (int) $this->mes, $filas);
            $this->alert('success', 'Costos del mes guardados.');
            $this->recargarTabla();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function verCampania(int $campaniaId): void
    {
        $this->tarjeta = app(CostosMensualConsulta::class)->tarjeta($campaniaId, (int) $this->anio, (int) $this->mes);
        $this->mostrarTarjeta = (bool) $this->tarjeta;
    }

    /** Abre el modal de cierre de campaña (CampaniaCerrarComponent) con la fecha sugerida por la cosecha. */
    public function cerrarCampania(): void
    {
        if (!$this->tarjeta) {
            return;
        }
        $this->mostrarTarjeta = false;
        $this->dispatch('cerrarCampania', campaniaId: $this->tarjeta['id']);
    }

    /** Al cerrar la campaña se actualiza la lista y se vuelve a mostrar su tarjeta ya cerrada. */
    #[On('campaniaCerrada')]
    public function campaniaCerrada(int $campaniaId): void
    {
        if ($this->tarjeta && $this->tarjeta['id'] === $campaniaId) {
            $this->verCampania($campaniaId);
        }
    }

    #[On('distribucionAprobada')]
    public function distribucionAprobada(): void
    {
        // render() vuelve a leer la fecha de la distribución
    }

    public function bloquearCampo(string $campo, bool $bloquear = true): void
    {
        $this->autorizar();
        try {
            app(CampoBloqueoProceso::class)->cambiar($campo, $bloquear);
            $this->alert('success', $bloquear ? "Campo {$campo} bloqueado: ya no aparece en las listas." : "Campo {$campo} desbloqueado.");
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function exportar()
    {
        try {
            $excel = app(CostosMensualExcel::class);
            $libro = $excel->generar((int) $this->anio, (int) $this->mes);

            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $excel->nombreArchivo((int) $this->anio, (int) $this->mes));
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function recargarTabla(): void
    {
        $costos = app(CostosMensualConsulta::class)->costos((int) $this->anio, (int) $this->mes);
        $this->dispatch('costosMensualesRecargar', filas: $costos['filas']);
    }

    private function autorizar(): void
    {
        if (!Gate::allows(Permisos::CONTABILIDAD_COSTO_MENSUAL_GESTIONAR)) {
            abort(403, 'No tienes permiso para modificar los costos mensuales.');
        }
    }

    public function render()
    {
        $consulta = app(CostosMensualConsulta::class);
        $campanias = $consulta->campanias((int) $this->anio, (int) $this->mes);
        $filas = collect($campanias['filas']);
        $conCampania = $filas->whereNotNull('campania_id');

        return view('livewire.costos.contabilidad-costos-mensuales-detalle-component', [
            'campanias' => $campanias,
            'costos' => $consulta->costos((int) $this->anio, (int) $this->mes),
            'resumen' => [
                'campanias' => $conCampania->count(),
                'abiertas' => $conCampania->whereNull('fecha_fin')->count(),
                'por_cerrar' => $conCampania->whereNull('fecha_fin')->whereNotNull('por_cerrar')->count(),
                'area' => $conCampania->unique('campo')->sum('area'),
                'con_avisos' => $conCampania->filter(fn($f) => $f['avisos'])->count(),
            ],
        ]);
    }
}
