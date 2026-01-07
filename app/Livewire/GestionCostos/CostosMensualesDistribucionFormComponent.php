<?php

namespace App\Livewire\GestionCostos;
use App\Models\CampoCampania;
use App\Models\CostoMensual;
use App\Services\Campania\ValidadorRangosCampania;
use App\Services\Contabilidad\CostosMensualesServicio;
use App\Services\Contabilidad\DistribucionCostoMensualServicio;
use App\Support\DistribucionGastosMensuales;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CostosMensualesDistribucionFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormDistribucionCostosMensuales = false;
    public $distribucionCalculada = [];
    public $totalesDistribuidos = 0;
    public $costoMensual;
    public $totalesCalculados = [];
    public $totalesReales = [];
    public $totalesDiferencia = [];
    protected $listeners = ['distribuirCostosMensuales'];
    public function distribuirCostosMensuales(int $costoMensualId): void
    {
        try {
            $costoMensual = CostoMensual::findOrFail($costoMensualId);
            $advertencias = ValidadorRangosCampania::validarCampos();

            $advertenciasPorCampania = collect($advertencias)
                ->flatMap(function ($campo) {
                    return collect($campo['errores'])
                        ->flatMap(function ($error) {
                            return collect($error['campania_ids'])->map(function ($campaniaId) use ($error) {
                                return [
                                    'campania_id' => $campaniaId,
                                    'tipo' => $error['tipo'],
                                    'mensaje' => $error['mensaje'],
                                ];
                            });
                        });
                })
                ->groupBy('campania_id');

            $this->costoMensual = $costoMensual;

            $anio = $costoMensual->anio;
            $mes = $costoMensual->mes;

            $this->totalesReales = [
                'fijo_administrativo' => $costoMensual->fijo_administrativo,
                'fijo_financiero' => $costoMensual->fijo_financiero,
                'fijo_gastos_oficina' => $costoMensual->fijo_gastos_oficina,
                'fijo_depreciaciones' => $costoMensual->fijo_depreciaciones,
                'fijo_costo_terreno' => $costoMensual->fijo_costo_terreno,
                'operativo_servicios_fundo' => $costoMensual->operativo_servicios_fundo,
                'operativo_mano_obra_indirecta' => $costoMensual->operativo_mano_obra_indirecta,
            ];

            $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
            $finMes = (clone $inicioMes)->endOfMonth();

            $campanias = CampoCampania::query()
                ->select('id', 'nombre_campania', 'fecha_inicio', 'fecha_fin')
                ->where('fecha_inicio', '<=', $finMes)
                ->where(function ($q) use ($inicioMes) {
                    $q->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $inicioMes);
                })
                ->get()
                ->map(function ($c) use ($advertenciasPorCampania) {

                    $errores = $advertenciasPorCampania->get($c->id, collect());

                    return [
                        'campania_id' => $c->id,
                        'nombre_campania' => $c->nombre_campania,
                        'fecha_inicio' => $c->fecha_inicio,
                        'fecha_fin' => $c->fecha_fin,

                        // flags de validación
                        'warning' => $errores->isNotEmpty(),
                        'errores' => $errores->values()->toArray(),
                    ];
                })
                ->toArray();

            $erroresCriticos = collect($campanias)
                ->flatMap(fn($c) => $c['errores'] ?? [])
                ->where('tipo', 'superposicion');

            $erroresCriticosSinCierre = collect($campanias)
                ->flatMap(fn($c) => $c['errores'] ?? [])
                ->where('tipo', 'campania_sin_cierre');



            if ($erroresCriticos->isNotEmpty()) {
                throw new \Exception(
                    'Existen campañas con superposición de fechas. ' .
                    'Revise las advertencias antes de continuar.'
                );
            }
            if ($erroresCriticosSinCierre->isNotEmpty()) {
                throw new \Exception(
                    'Existen campañas sin fechas de cierre. ' .
                    'Revise las advertencias antes de continuar.'
                );
            }

            $this->distribucionCalculada =
                DistribucionGastosMensuales::calcular(
                    $anio,
                    $mes,
                    $this->totalesReales,
                    $campanias
                );

            $this->totalesCalculados = collect($this->distribucionCalculada)->reduce(function ($carry, $fila) {
                foreach ([
                    'fijo_administrativo',
                    'fijo_financiero',
                    'fijo_gastos_oficina',
                    'fijo_depreciaciones',
                    'fijo_costo_terreno',
                    'operativo_servicios_fundo',
                    'operativo_mano_obra_indirecta',
                ] as $campo) {
                    $carry[$campo] = ($carry[$campo] ?? 0)
                        + ($fila['monto_' . $campo] ?? 0);
                }
                return $carry;
            }, []);

            $this->totalesDiferencia = [];
            foreach ($this->totalesReales as $campo => $real) {
                $diff = round($real - ($this->totalesCalculados[$campo] ?? 0), 2);

                // Normalizar -0.0 a 0.0
                $this->totalesDiferencia[$campo] = abs($diff) < 0.01 ? 0.0 : $diff;
            }


            $this->mostrarFormDistribucionCostosMensuales = true;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function aprobarDistribucion()
    {
        try {

            app(DistribucionCostoMensualServicio::class)
                ->guardar($this->costoMensual->id, $this->distribucionCalculada);

            $this->alert('success', 'Distribución mensual guardada correctamente');
            $this->mostrarFormDistribucionCostosMensuales = false;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-costos.costos-mensuales-distribucion-form-component');
    }
}