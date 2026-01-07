<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CostoMensual;
use App\Services\Campania\ValidadorRangosCampania;
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
    public $mes;
    public $anio;
    public $areaProduccion;
    public $fechaDistribucion;
    public $advertencias = [];
    public $costoMensualId;
    public function mount()
    {
        $this->advertencias = ValidadorRangosCampania::validarCampos();
        $this->listarCostos();
        $this->listarCampos();
        $this->listarCostoMensualId();
        $this->fechaDistribucion = Carbon::createFromDate($this->anio, $this->mes, 1)->lastOfMonth()->format('Y-m-d');

    }
    public function listarCostoMensualId()
    {
        $this->costoMensualId = CostoMensual::where('anio', $this->anio)
            ->where('mes', $this->mes)
            ->first()
                ?->id;
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
            $this->listarCostoMensualId();
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar los costos.');
        }
    }
    public function listarCampos()
    {
        $anio = (int) $this->anio;
        $mes = (int) $this->mes;

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = (clone $inicioMes)->endOfMonth();

        // Indexar advertencias por campo + campaña
        $advertenciasIndexadas = collect($this->advertencias)
            ->flatMap(function ($campo) {
                return collect($campo['errores'])->flatMap(function ($error) use ($campo) {
                    return collect($error['campania_ids'] ?? [$error['campania_id']])
                        ->map(function ($campaniaId) use ($campo) {
                            return [
                                'nombre' => $campo['campo_nombre'],
                                'campania_id' => $campaniaId,
                            ];
                        });
                });
            });

        $this->campos = Campo::with([
            'campanias' => function ($query) use ($inicioMes, $finMes) {
                $query
                    ->where('fecha_inicio', '<=', $finMes)
                    ->where(function ($q) use ($inicioMes) {
                        $q->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $inicioMes);
                    })
                    ->orderBy('fecha_inicio');
            }
        ])
            ->orderBy('orden')
            ->get()
            ->flatMap(function ($campo) use ($inicioMes, $finMes, $advertenciasIndexadas) {

                // Caso 1: campo SIN campañas → una fila inactiva
                if ($campo->campanias->isEmpty()) {
                    return [
                        [
                            'nombre' => $campo->nombre,
                            'area' => $campo->area,

                            'campania_id' => null,
                            'campania' => null,
                            'fecha_inicio' => null,
                            'fecha_fin' => null,

                            'activo' => false,
                            'warning' => false,
                        ]
                    ];
                }

                // Caso 2: una o más campañas → clonar filas
                return $campo->campanias->map(function ($campania) use ($campo, $inicioMes, $finMes, $advertenciasIndexadas) {

                    $activo =
                        $campania->fecha_inicio <= $finMes &&
                        (
                            is_null($campania->fecha_fin)
                            || $campania->fecha_fin >= $inicioMes
                        );

                    $warning = $advertenciasIndexadas->contains(function ($item) use ($campo, $campania) {
                        return
                            $item['nombre'] === $campo->nombre &&
                            $item['campania_id'] === $campania->id;
                    });

                    return [
                        'nombre' => $campo->nombre,
                        'area' => $campo->area,

                        'campania_id' => $campania->id,
                        'campania' => $campania->nombre_campania,
                        'fecha_inicio' => $campania->fecha_inicio,
                        'fecha_fin' => $campania->fecha_fin,

                        'activo' => $activo,
                        'warning' => $warning,
                    ];
                });
            })
            ->values();

        $this->calcularAreaProduccion();
    }

    public function calcularAreaProduccion()
    {
        $this->areaProduccion = $this->campos
            ->where('activo', true) // Filtra solo los campos activos
            ->sum('area');
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
