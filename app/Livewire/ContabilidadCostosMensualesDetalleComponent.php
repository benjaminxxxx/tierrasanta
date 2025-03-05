<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CamposActivos;
use App\Models\CostoMensual;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ContabilidadCostosMensualesDetalleComponent extends Component
{
    use LivewireAlert;
    public $campos = [];
    public $campoSeleccionado = [];
    public $modoEdicion = false;
    public $costos_mensuales = [];
    public $fijo_administrativo_costo_blanco;
    public $fijo_administrativo_costo_negro;
    public $fijo_financiero_costo_blanco;
    public $fijo_financiero_costo_negro;
    public $fijo_gastos_oficina_costo_blanco;
    public $fijo_gastos_oficina_costo_negro;
    public $fijo_depreciaciones_costo_blanco;
    public $fijo_depreciaciones_costo_negro;
    public $fijo_terreno_costo_blanco;
    public $fijo_terreno_costo_negro;
    public $operativo_servicios_fundo_costo_blanco;
    public $operativo_servicios_fundo_costo_negro;
    public $operativo_mano_obra_indirecta_costo_blanco;
    public $operativo_mano_obra_indirecta_costo_negro;
    public $mes, $anio;
    public function mount()
    {
        $this->listarCostos();
        $this->listarCampos();
    }
    public function importarMesAnterior()
    {
        // Calcular el mes y año anterior de manera correcta
        $fechaActual = Carbon::createFromDate($this->anio, $this->mes, 1);
        $fechaAnterior = (clone $fechaActual)->subMonth();

        $mesOrigen = $fechaAnterior->month;
        $anioOrigen = $fechaAnterior->year;
        $mesDestino = $fechaActual->month;
        $anioDestino = $fechaActual->year;

        // Obtener los campos activos en el mes anterior
        $camposActivos = CamposActivos::where('mes', $mesOrigen)
            ->where('anio', $anioOrigen)
            ->pluck('campo_nombre');

        if ($camposActivos->isEmpty()) {
            return $this->alert('success', 'No hay datos por importar'); // No hay datos para importar
        }

        // Obtener los registros que ya existen en el mes destino
        $camposExistentes = CamposActivos::where('mes', $mesDestino)
            ->where('anio', $anioDestino)
            ->pluck('campo_nombre')
            ->toArray();

        // Filtrar solo los campos que no están en el destino para evitar duplicados
        $nuevosRegistros = $camposActivos->diff($camposExistentes)->map(function ($campo) use ($mesDestino, $anioDestino) {
            return [
                'campo_nombre' => $campo,
                'mes' => $mesDestino,
                'anio' => $anioDestino,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        if ($nuevosRegistros->isNotEmpty()) {
            CamposActivos::insert($nuevosRegistros->toArray());
            $this->alert('success', count($nuevosRegistros) . ' campos activados correctamente');
            $this->listarCampos();
        } else {
            $this->alert('success', 'El mes pasado contiene los mismos campos activos');
        }
    }
    public function guardarCambios()
    {
        try {
            $data = [
                'anio' => $this->anio,
                'mes' => $this->mes,
                'fijo_administrativo_blanco' => $this->fijo_administrativo_costo_blanco,
                'fijo_administrativo_negro' => $this->fijo_administrativo_costo_negro,
                'fijo_financiero_blanco' => $this->fijo_financiero_costo_blanco,
                'fijo_financiero_negro' => $this->fijo_financiero_costo_negro,
                'fijo_gastos_oficina_blanco' => $this->fijo_gastos_oficina_costo_blanco,
                'fijo_gastos_oficina_negro' => $this->fijo_gastos_oficina_costo_negro,
                'fijo_depreciaciones_blanco' => $this->fijo_depreciaciones_costo_blanco,
                'fijo_depreciaciones_negro' => $this->fijo_depreciaciones_costo_negro,
                'fijo_costo_terreno_blanco' => $this->fijo_terreno_costo_blanco,
                'fijo_costo_terreno_negro' => $this->fijo_terreno_costo_negro,
                'operativo_servicios_fundo_blanco' => $this->operativo_servicios_fundo_costo_blanco,
                'operativo_servicios_fundo_negro' => $this->operativo_servicios_fundo_costo_negro,
                'operativo_mano_obra_indirecta_blanco' => $this->operativo_mano_obra_indirecta_costo_blanco,
                'operativo_mano_obra_indirecta_negro' => $this->operativo_mano_obra_indirecta_costo_negro,
            ];

            // Buscar si ya existe un registro con el mismo mes y año
            $costoMensual = CostoMensual::where('anio', $this->anio)
                ->where('mes', $this->mes)
                ->first();

            if ($costoMensual) {
                // Si existe, actualiza el registro
                $costoMensual->update($data);
                $this->alert('success', 'Costos actualizados correctamente.');
            } else {
                // Si no existe, crea un nuevo registro
                CostoMensual::create($data);
                $this->alert('success', 'Costos registrados correctamente.');
            }
            $this->modoEdicion = false;
            $this->listarCostos();
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar los costos.');
        }
    }
    public function listarCampos()
    {
        /**
         * Se va a realizar dos consideraciones para la campaña
         * digamos que hay en un mes dos campañas
         * campaña por cerrarse fecha de inicio octubre 10 del 2024 - fecha de fin febrero 20 del 2025
         * campaña aperturada fecha de inicio febrero 23 del 2025 fecha de fin no disponible aun
         * ya que estamos en el mes de febrero se debe considerar la ultima o sea campaña 2
         * las campañas no se cruzan entre fechas siempre es una despues de la otra
         */
        $this->campos = Campo::with([
            'camposActivos' => function ($query) {
                $query->where('anio', $this->anio)
                      ->where('mes', $this->mes);
            },
            'campanias' => function ($query) {
                $query->whereYear('fecha_inicio', $this->anio)
                      ->whereMonth('fecha_inicio', $this->mes)
                      ->where(function ($q) {
                          $q->whereDate('fecha_fin', '>=', now()->toDateString()) // Considerar si aún está activa
                            ->orWhereNull('fecha_fin'); // Campañas abiertas
                      })
                      ->orderBy('fecha_inicio', 'desc') // Tomar la más reciente dentro del mes
                      ->limit(1); // Solo la última
            }
        ])->orderBy('orden')->get()->map(function ($campo) {
            return [
                'nombre' => $campo->nombre,
                'activo' => $campo->camposActivos->isNotEmpty(),
                'campania' => $campo->campanias->isNotEmpty() ? $campo->campanias->first()->nombre_campania : '',
                'area' => $campo->area
            ];
        });
        

        // Inicializar la variable campoSeleccionado
        $this->campoSeleccionado = $this->campos->pluck('activo', 'nombre')->toArray();
    }
    public function updatedCampoSeleccionado($activado, $campo)
    {

        $data = [
            'anio' => $this->anio,
            'mes' => $this->mes,
            'campo_nombre' => $campo,
        ];

        if ($activado) {
            CamposActivos::updateOrCreate($data, $data);
            $this->alert('success', 'Campo agregado a la lista.');
        } else {
            CamposActivos::where($data)->delete();
            $this->alert('success', 'Campo quitado de la lista.');
        }
    }

    public function listarCostos()
    {
        try {
            $costos = CostoMensual::where('anio', $this->anio)
                ->where('mes', $this->mes)
                ->first();

            if (!$costos) {
                $this->alert('warning', 'No se encontraron costos para el mes y año seleccionados.');
                $this->resetCostos();
                return;
            }

            // Convertir el modelo a array y formatearlo para la vista
            $this->costos_mensuales = [
                'fijo_administrativo' => [
                    'costo_blanco' => $costos->fijo_administrativo_blanco ?? 0,
                    'costo_negro' => $costos->fijo_administrativo_negro ?? 0,
                ],
                'fijo_financiero' => [
                    'costo_blanco' => $costos->fijo_financiero_blanco ?? 0,
                    'costo_negro' => $costos->fijo_financiero_negro ?? 0,
                ],
                'fijo_gastos_oficina' => [
                    'costo_blanco' => $costos->fijo_gastos_oficina_blanco ?? 0,
                    'costo_negro' => $costos->fijo_gastos_oficina_negro ?? 0,
                ],
                'fijo_depreciaciones' => [
                    'costo_blanco' => $costos->fijo_depreciaciones_blanco ?? 0,
                    'costo_negro' => $costos->fijo_depreciaciones_negro ?? 0,
                ],
                'fijo_terreno' => [
                    'costo_blanco' => $costos->fijo_costo_terreno_blanco ?? 0,
                    'costo_negro' => $costos->fijo_costo_terreno_negro ?? 0,
                ],
                'operativo_servicios_fundo' => [
                    'costo_blanco' => $costos->operativo_servicios_fundo_blanco ?? 0,
                    'costo_negro' => $costos->operativo_servicios_fundo_negro ?? 0,
                ],
                'operativo_mano_obra_indirecta' => [
                    'costo_blanco' => $costos->operativo_mano_obra_indirecta_blanco ?? 0,
                    'costo_negro' => $costos->operativo_mano_obra_indirecta_negro ?? 0,
                ],
            ];
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al listar los costos.');
        }
    }

    /**
     * Reinicia los valores de costos a cero
     */
    private function resetCostos()
    {
        $this->costos_mensuales = [
            'fijo_administrativo' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'fijo_financiero' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'fijo_gastos_oficina' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'fijo_depreciaciones' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'fijo_terreno' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'operativo_servicios_fundo' => ['costo_blanco' => 0, 'costo_negro' => 0],
            'operativo_mano_obra_indirecta' => ['costo_blanco' => 0, 'costo_negro' => 0],
        ];
    }

    public function editarCostos()
    {
        if (!empty($this->costos_mensuales)) {

            $this->fijo_administrativo_costo_blanco = $this->costos_mensuales['fijo_administrativo']['costo_blanco'] ?? 0;
            $this->fijo_administrativo_costo_negro = $this->costos_mensuales['fijo_administrativo']['costo_negro'] ?? 0;

            $this->fijo_financiero_costo_blanco = $this->costos_mensuales['fijo_financiero']['costo_blanco'] ?? 0;
            $this->fijo_financiero_costo_negro = $this->costos_mensuales['fijo_financiero']['costo_negro'] ?? 0;

            $this->fijo_gastos_oficina_costo_blanco = $this->costos_mensuales['fijo_gastos_oficina']['costo_blanco'] ?? 0;
            $this->fijo_gastos_oficina_costo_negro = $this->costos_mensuales['fijo_gastos_oficina']['costo_negro'] ?? 0;

            $this->fijo_depreciaciones_costo_blanco = $this->costos_mensuales['fijo_depreciaciones']['costo_blanco'] ?? 0;
            $this->fijo_depreciaciones_costo_negro = $this->costos_mensuales['fijo_depreciaciones']['costo_negro'] ?? 0;

            $this->fijo_terreno_costo_blanco = $this->costos_mensuales['fijo_terreno']['costo_blanco'] ?? 0;
            $this->fijo_terreno_costo_negro = $this->costos_mensuales['fijo_terreno']['costo_negro'] ?? 0;

            $this->operativo_servicios_fundo_costo_blanco = $this->costos_mensuales['operativo_servicios_fundo']['costo_blanco'] ?? 0;
            $this->operativo_servicios_fundo_costo_negro = $this->costos_mensuales['operativo_servicios_fundo']['costo_negro'] ?? 0;

            $this->operativo_mano_obra_indirecta_costo_blanco = $this->costos_mensuales['operativo_mano_obra_indirecta']['costo_blanco'] ?? 0;
            $this->operativo_mano_obra_indirecta_costo_negro = $this->costos_mensuales['operativo_mano_obra_indirecta']['costo_negro'] ?? 0;
        }

        $this->modoEdicion = true;
    }

    public function cancelarEdicion()
    {
        $this->modoEdicion = false;
    }
    public function render()
    {
        return view('livewire.contabilidad-costos-mensuales-detalle-component');
    }
}
