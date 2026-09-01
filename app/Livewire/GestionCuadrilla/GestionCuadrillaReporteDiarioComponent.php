<?php

namespace App\Livewire\GestionCuadrilla;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Livewire\Traits\ConManejarErrores;
use App\Models\Campo;
use App\Models\Labores;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Modulos\ReporteDiarioCuadrillaServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaReporteDiarioComponent extends Component
{
    use LivewireAlert, ConFechaReporteDia, ConManejarErrores;

    #region VARIABLES E INICIALIZACION
    public $trabajadores = [];
    public $totalColumnas = 0;
    public $labores = [];
    public $campos = [];
    public $tramos = [];
    public $tramoSeleccionadoId;
    protected $listeners = ['registroDetalleHorasExterno' => 'refrescarTabla', 'guardarReporteConfirmado'];
    protected ReporteDiarioCuadrillaServicio $reporteDiarioCuadrillaServicio;
    public function boot(ReporteDiarioCuadrillaServicio $reporteDiarioCuadrillaServicio)
    {
        $this->reporteDiarioCuadrillaServicio = $reporteDiarioCuadrillaServicio;
    }
    public function mount()
    {
        $this->inicializarFecha();
        $this->inicializarValores();
        $this->cargarDatosDeReporte();
    }
    public function updatedTramoSeleccionadoId($tramoId)
    {
        $this->refrescarTabla($this->fecha);
    }
    protected function despuesFechaModificada(string $fecha)
    {
        $this->detectarTramos($fecha);
        $this->refrescarTabla($fecha);
    }
    #endregion

    #region METODOS
    private function detectarTramos($fecha)
    {
        $this->tramos = $this->reporteDiarioCuadrillaServicio->obtenerTramosEnFecha($fecha);

        if ($this->tramos && $this->tramos->count() >= 1) {
            $this->tramoSeleccionadoId = $this->tramos->first()->id;

        }
    }
    private function inicializarValores()
    {
        $this->labores = Labores::get()->pluck('codigo')->toArray();
        $this->campos = Campo::get()->pluck('nombre')->toArray();
    }
    private function cargarDatosDeReporte()
    {
        try {
            if (!$this->tramoSeleccionadoId || !$this->fecha) {
                return;
            }
            $resultado = $this->reporteDiarioCuadrillaServicio->obtenerDatosParaReporteDiario($this->fecha, $this->tramoSeleccionadoId);
            $this->trabajadores = $resultado['data'];
            $this->totalColumnas = $resultado['total_columnas'];
        } catch (\Throwable $e) {
            $this->manejarError($e, 'Error al cargar los datos del reporte diario');
        }
    }
    /*
    public function storeTableDataGuardarActividadDiaria($datos)
    {
        try {

            $this->reporteDiarioCuadrillaServicio->guardarReporteDiario($this->fecha, $datos,$this->totalColumnas);
            $this->refrescarTabla($this->fecha); 
            $this->alert('success', 'Registro actualizado correctamente');

        } catch (ValidationException $ex) {
            $this->alert('error', implode("\n", $ex->validator->errors()->all()));
        } catch (\Throwable $e) {
            $this->manejarError($e,$e->getMessage());
        }
    }*/
    public function storeTableDataGuardarActividadDiaria($datos)
    {
        try {
            // 1. Validar si hay bonos que se van a perder por quitar labores
            $afectados = CuadrilleroServicio::detectarBonosAEliminar($this->fecha, $datos);

            if (!empty($afectados)) {
                // 1. Estructura visual adaptada a contenedor blanco (SweetAlert2)
                $htmlContent = '<div class="text-left text-sm space-y-3 mt-2 font-sans leading-relaxed">';

                // Encabezado de advertencia destacado
                $htmlContent .= '<div class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-md">';
                $htmlContent .= '<p class="font-bold text-red-800 text-xs uppercase tracking-wide">Atención requerida</p>';
                $htmlContent .= '<p class="text-red-700 text-xs font-medium mt-0.5">Los siguientes cuadrilleros perderán sus bonos asignados al modificar o eliminar sus labores:</p>';
                $htmlContent .= '</div>';

                // Listado de trabajadores y detalles en fondo contrastado
                $htmlContent .= '<div class="max-h-52 overflow-y-auto space-y-2 pr-1 border border-slate-200 rounded-lg p-2 bg-slate-50">';
                foreach ($afectados as $af) {
                    $htmlContent .= '<div class="border-b border-slate-200 last:border-0 pb-2 last:pb-0 text-slate-800">';
                    $htmlContent .= "<strong class='text-slate-900 font-semibold block text-xs tracking-wide uppercase'>• {$af['nombre']}</strong>";
                    $htmlContent .= "<ul class='mt-1 pl-4 list-disc space-y-0.5 text-slate-600 text-xs font-normal'>";
                    foreach ($af['bonos'] as $b) {
                        $htmlContent .= "<li>{$b['actividad']} <span class='font-semibold text-slate-900'>(Bono: S/ " . number_format($b['monto'], 2) . ")</span></li>";
                    }
                    $htmlContent .= "</ul></div>";
                }
                $htmlContent .= '</div>';

                // Pie con llamada de confirmación
                $htmlContent .= '<div class="bg-amber-50 border border-amber-200 rounded-md text-amber-900 text-xs font-medium">';
                $htmlContent .= 'Al confirmar, el sistema eliminará automáticamente dichos registros para mantener la consistencia.';
                $htmlContent .= '</div></div>';

                // 2. Disparo de confirmación con clases de botones estilizados
                $this->confirm('¿Desea guardar los cambios?', [
                    'text' => $htmlContent,
                    'html' => $htmlContent,
                    'showCancelButton' => true,
                    'confirmButtonText' => 'Sí, eliminar y guardar',
                    'cancelButtonText' => 'Cancelar',
                    'onConfirmed' => 'guardarReporteConfirmado',
                    'data' => [
                        'datos' => $datos,
                    ],
                ]);

                return;
            }

            // Si no hay afectaciones, guardar directamente
            $this->ejecutarGuardado($datos);

        } catch (ValidationException $ex) {
            $this->alert('error', implode("\n", $ex->validator->errors()->all()));
        } catch (\Throwable $e) {
            $this->manejarError($e, $e->getMessage());
        }
    }

    public function guardarReporteConfirmado($data)
    {
        $datos = $data['datos'] ?? [];
        $this->ejecutarGuardado($datos);
    }

    private function ejecutarGuardado(array $datos): void
    {
        try {
            $this->reporteDiarioCuadrillaServicio->guardarReporteDiario($this->fecha, $datos, $this->totalColumnas);
            $this->refrescarTabla($this->fecha);
            $this->alert('success', 'Registro actualizado correctamente');
        } catch (\Throwable $e) {
            $this->manejarError($e, $e->getMessage());
        }
    }
    public function refrescarTabla($fecha)
    {
        $this->fecha = $fecha;
        $this->cargarDatosDeReporte();
        $this->dispatch('actualizarTablaCuadrilleros', $this->trabajadores, $this->totalColumnas);
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-diario-component');
    }
    #endregion
}