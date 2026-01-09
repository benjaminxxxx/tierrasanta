<?php

namespace App\Livewire\GestionEvaluacion;

use App\Models\CampoCampania;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProyeccionRendimientoPodaComponent extends Component
{
    use LivewireAlert;
    public $campania;
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $campaniasPorCampo = [];
    public $table = [];
    public $idTable;
    public $tamanioMuestra;
    public $metrosCamaHa;
    protected $listeners = ['storeTableDataProyeccionPoda','campaniaInsertada'=>'obtenerCampanias'];
    public function mount($campania = null)
    {
        $this->campania = $campania;
        $this->idTable = 'table_' . Str::random(10);
    }
    public function updatedCampoSeleccionado($valor)
    {
        $this->campaniasPorCampo = CampoCampania::where('campo', $valor)->get();
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        $this->renderizarTabla();
    }
    public function obtenerCampanias(){
        if ($this->campoSeleccionado) {
            $this->campaniasPorCampo = CampoCampania::where('campo', $this->campoSeleccionado)->get();
        }
    }
    public function updatedCampaniaSeleccionada($valor)
    {
        $this->campania = CampoCampania::find($valor);
        if ($this->campania) {
            $this->tamanioMuestra = $this->campania->proj_rdto_poda_muestra;
            $this->metrosCamaHa = $this->campania->proj_rdto_metros_cama_ha;
        } else {
            $this->tamanioMuestra = null;
            $this->metrosCamaHa = null;
        }
        $this->renderizarTabla();
    }
    public function renderizarTabla()
    {
        if (!$this->campania) {
            $this->dispatch('recargarRendimientoPoda', []);
            return;
        }

        // Obtener las proyecciones existentes, indexadas por número de muestra
        $proyecciones = $this->campania->proyeccionesRendimientosPoda()
            ->get()
            ->keyBy('nro_muestra');

        $data = [];

        // Variables para calcular promedios
        $suma_rdto = 0;
        $cont_rdto = 0;

        $suma_relacion = 0;
        $cont_relacion = 0;

        // Generar siempre 8 filas
        for ($i = 1; $i <= 8; $i++) {
            $registro = $proyecciones->get($i); // puede ser null

            $rdto = $registro->rdto_hectarea_kg ?? null;
            $relacion = $registro->relacion_fresco_seco ?? null;

            // Acumular solo si no es nulo
            if (is_numeric($rdto)) {
                $suma_rdto += $rdto;
                $cont_rdto++;
            }
            if (is_numeric($relacion)) {
                $suma_relacion += $relacion;
                $cont_relacion++;
            }

            $data[] = [
                'nro_muestra' => $i,
                'peso_fresco_kg' => $registro->peso_fresco_kg ?? null,
                'peso_seco_kg' => $registro->peso_seco_kg ?? null,
                'rdto_hectarea_kg' => $rdto,
                'relacion_fresco_seco' => $relacion,
            ];
        }

        // Calcular promedios, evitar división por cero
        $promedio_rdto = $cont_rdto > 0 ? round($suma_rdto / $cont_rdto, 2) : null;
        $promedio_relacion = $cont_relacion > 0 ? round($suma_relacion / $cont_relacion, 4) : null;

        // Agregar fila de resumen con promedio
        $data[] = [
            'nro_muestra' => null,
            'peso_fresco_kg' => null,
            'peso_seco_kg' => 'RDTO PROMEDIO',
            'rdto_hectarea_kg' => $promedio_rdto,
            'relacion_fresco_seco' => $promedio_relacion,
        ];
        $this->campania->update([
            'proj_rdto_prom_rdto_ha' => $promedio_rdto,
            'proj_rdto_rel_fs' => $promedio_relacion,
        ]);

        $this->dispatch('recargarRendimientoPoda', $data);
    }

    public function storeTableDataProyeccionPoda($datos)
    {
        if (!$this->campania) {
            return;
        }

        $muestra = $this->campania->proj_rdto_poda_muestra;
        $metros = $this->campania->proj_rdto_metros_cama_ha;

        foreach ($datos as $fila) {
            $peso_fresco = floatval($fila['peso_fresco_kg'] ?? 0);
            $peso_seco = floatval($fila['peso_seco_kg'] ?? 0);

            // Solo guardar si alguno de los dos está presente
            if ($peso_fresco || $peso_seco) {
                $rdto = ($peso_seco && $muestra && $metros)
                    ? ($peso_seco / $muestra) * $metros
                    : null;

                $relacion = ($peso_fresco && $peso_seco)
                    ? $peso_fresco / $peso_seco
                    : null;

                $this->campania->proyeccionesRendimientosPoda()->updateOrCreate(
                    ['nro_muestra' => $fila['nro_muestra']],
                    [
                        'peso_fresco_kg' => $peso_fresco ?: null,
                        'peso_seco_kg' => $peso_seco ?: null,
                        'rdto_hectarea_kg' => $rdto,
                        'relacion_fresco_seco' => $relacion,
                    ]
                );
            } else {
                // Si no hay datos, eliminamos la fila (opcional)
                $this->campania->proyeccionesRendimientosPoda()
                    ->where('nro_muestra', $fila['nro_muestra'])
                    ->delete();
            }
        }
        $this->renderizarTabla();
        $this->alert('success', 'Datos guardados correctamente.');
    }
    public function guardarDatosRendimientoPoda()
    {
        if (!$this->campania) {
            return;
        }

        // Validar y convertir a números
        $muestra = is_numeric($this->tamanioMuestra) ? intval($this->tamanioMuestra) : null;
        $metros = is_numeric($this->metrosCamaHa) ? floatval($this->metrosCamaHa) : null;

        // Opcional: puedes validar que no sean nulos si es requerido
        if (!$muestra || !$metros) {
            $this->alert('error', 'Los campos de muestra y metros cama/ha son obligatorios.');
            return;
        }

        $this->campania->update([
            'proj_rdto_poda_muestra' => $muestra,
            'proj_rdto_metros_cama_ha' => $metros,
        ]);
        $this->dispatch('guardarDetallePoda');
        $this->alert('success', 'Parámetros actualizados correctamente.');
    }


    public function render()
    {
        return view('livewire.gestion-evaluacion.proyeccion-rendimiento-poda-component');
    }
}
