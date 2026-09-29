<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexReporteFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioInsumoKardexReporte = false;
    public ?int $reporteId = null;
    public $anio;
    public $nombre;
    public $tipoKardex = 'negro';
    public array $gruposDisponibles = [];
    public array $gruposSeleccionados = [];
    protected $listeners = ['nuevoInsumoKardexReporte', 'editarInsumoKardexReporte'];

    public function mount()
    {
        $this->gruposDisponibles = InsKardexReporte::gruposDisponibles();
    }

    public function nuevoInsumoKardexReporte()
    {
        $this->resetForm();
        $this->anio = now()->year;
        // Lo habitual: fertilizantes y pesticidas juntos, combustible aparte
        $this->gruposSeleccionados = array_values(array_intersect(['fertilizante', 'pesticida'], $this->gruposDisponibles));
        $this->mostrarFormularioInsumoKardexReporte = true;
    }

    public function editarInsumoKardexReporte($reporteId)
    {
        $reporte = InsKardexReporte::find($reporteId);
        if (!$reporte) {
            return $this->alert('error', 'El reporte de kardex no existe');
        }
        $this->resetForm();
        $this->reporteId = $reporte->id;
        $this->nombre = $reporte->nombre;
        $this->anio = $reporte->anio;
        $this->tipoKardex = $reporte->tipo_kardex;
        $this->gruposSeleccionados = $reporte->grupos_ordenados;
        $this->mostrarFormularioInsumoKardexReporte = true;
    }

    public function toggleGrupo(string $grupo)
    {
        $this->gruposSeleccionados = in_array($grupo, $this->gruposSeleccionados, true)
            ? array_values(array_diff($this->gruposSeleccionados, [$grupo]))
            : InsKardexReporte::ordenarGrupos([...$this->gruposSeleccionados, $grupo]);
    }

    public function guardarInsumoKardexReporte()
    {
        $this->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'gruposSeleccionados' => 'required|array|min:1',
            'gruposSeleccionados.*' => 'in:' . implode(',', $this->gruposDisponibles),
            'tipoKardex' => 'required|string|in:blanco,negro',
        ], [
            'gruposSeleccionados.required' => 'Elige al menos un grupo operativo.',
            'gruposSeleccionados.min' => 'Elige al menos un grupo operativo.',
        ]);

        try {
            $datos = [
                // Siempre automático (también al editar): refleja año, tipo y grupos
                'nombre' => InsKardexReporte::nombreAutomatico($this->anio, $this->tipoKardex, $this->gruposSeleccionados),
                'anio' => $this->anio,
                'tipo_kardex' => $this->tipoKardex,
                'grupos_operativos' => InsKardexReporte::ordenarGrupos($this->gruposSeleccionados),
            ];

            if ($this->reporteId) {
                $reporte = InsKardexReporte::findOrFail($this->reporteId);
                $cambiaContenido = $reporte->anio != $datos['anio'] || $reporte->tipo_kardex !== $datos['tipo_kardex']
                    || $reporte->grupos_ordenados !== $datos['grupos_operativos'];
                $reporte->update($datos);
                if ($cambiaContenido) {
                    // Otro año/tipo/grupos: el índice y el Excel anteriores ya no corresponden
                    $reporte->detalles()->delete();
                    $reporte->update(['generado_at' => null]);
                }
                $mensaje = 'Reporte actualizado' . ($cambiaContenido ? '. Vuelve a generar el resumen.' : '');
            } else {
                InsKardexReporte::create($datos);
                $mensaje = 'Reporte creado. Ábrelo y usa "Generar resumen" para armar el Excel.';
            }

            $this->dispatch("insumoKardexRefrescar");
            $this->resetForm();
            $this->mostrarFormularioInsumoKardexReporte = false;
            $this->alert("success", $mensaje);
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['reporteId', 'anio', 'gruposSeleccionados', 'tipoKardex', 'nombre']);
    }

    public function render()
    {
        // Cuántos kardex entrarían con la selección actual (orientativo)
        $conteo = [];
        if ($this->anio && $this->tipoKardex) {
            $conteo = InsKardex::where('ins_kardexes.anio', $this->anio)
                ->where('ins_kardexes.tipo', $this->tipoKardex)
                ->join('productos', 'productos.id', '=', 'ins_kardexes.producto_id')
                ->join('ins_categorias', 'ins_categorias.codigo', '=', 'productos.categoria_codigo')
                ->selectRaw('ins_categorias.grupo_operativo as grupo, COUNT(*) as n')
                ->groupBy('ins_categorias.grupo_operativo')
                ->pluck('n', 'grupo')
                ->toArray();
        }

        return view('livewire.almacen.kardex.insumo-kardex-reporte-form-component', [
            'conteoKardex' => $conteo,
        ]);
    }
}
