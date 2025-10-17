<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\PlanDescuentoSp;
use App\Models\PlanDescuentoSPHistorico;
use Carbon\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConfiguracionDescuentoAfpComponent extends Component
{
    use LivewireAlert;
    public $descuentosSP;
    public $descuentos;
    public $descuentos65;
    public $informacion;
    public $fechas = [];
    public $fecha_inicio;
    public $descuentosSPHistorico;
    public $fechasRegistradas;

    public function mount()
    {

        $this->descuentosSP = PlanDescuentoSp::orderBy('orden', 'asc')->get();
        $this->generarFechas();
        $this->informacion = '';
    }
    public function generarFechas()
    {
        $inicio = Carbon::createFromDate(1993, 6, 1);
        $actual = Carbon::now();

        while ($actual > $inicio) {
            $this->fechas[] = $actual->format('Y-m');
            $actual->subMonth();
        }
        $this->fecha_inicio = Carbon::now()->format('Y-m');
    }

    public function eliminarDescuentos()
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');
            PlanDescuentoSPHistorico::whereDate('fecha_inicio', $fecha)->delete();


            $this->alert('success', 'Los montos para la feccha seleccionada han sido limpiados exitosamente.');
        } catch (\Exception $e) {
            // Captura cualquier excepción y muestra un mensaje de error
            $this->alert('error', 'Ocurrió un error al limpiar los montos: ' . $e->getMessage());
        }
    }
    public function generarCalculo()
    {
        if (!$this->informacion || trim($this->informacion) == '') {
            return $this->alert('error', 'No hay Data');
        }

        try {

            $valoresDescuentos = $this->parsearInformacion($this->informacion);


            // Recorrer los descuentos desde la base de datos
            foreach ($this->descuentosSP as $descuento) {
                $this->actualizarDescuento($descuento, $valoresDescuentos);
            }

            $this->alert('success', 'Campos Actualizados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $th->getMessage());
        }
    }
    protected function parsearInformacion($informacion)
    {
        $filas = explode("\n", $informacion);
        $valoresDescuentos = [];

        foreach ($filas as $fila) {
            $columnas = explode("\t", $fila);

            if (count($columnas) >= 5) {
                $referencia = strtoupper(trim($columnas[0]));
                $comisionSobreFlujo = $this->formatearPorcentaje($columnas[1]);
                $primaDeSeguros = $this->formatearPorcentaje($columnas[3]);
                $aporteObligatorio = $this->formatearPorcentaje($columnas[4]);

                $valoresDescuentos[$referencia] = [
                    'flujo' => $comisionSobreFlujo + $primaDeSeguros + $aporteObligatorio,
                    'flujo_65' => $comisionSobreFlujo + $aporteObligatorio,
                    'mixta' => $primaDeSeguros + $aporteObligatorio,
                    'mixta_65' => $aporteObligatorio,
                ];
            }
        }

        return $valoresDescuentos;
    }
    protected function actualizarDescuento(PlanDescuentoSp $descuento, array $valoresDescuentos)
    {
        if (isset($valoresDescuentos[$descuento->referencia])) {
            $valorDescuento = $valoresDescuentos[$descuento->referencia];
            $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');

            $porcentaje = 0;
            $porcentaje_65 = 0;


            if ($descuento->tipo == 'Flujo') {
                $porcentaje = $valorDescuento['flujo'];
                $porcentaje_65 = $valorDescuento['flujo_65'];
            } elseif ($descuento->tipo == 'Mixta') {
                $porcentaje = $valorDescuento['mixta'];
                $porcentaje_65 = $valorDescuento['mixta_65'];
            }

            PlanDescuentoSPHistorico::updateOrCreate(
                [
                    'fecha_inicio' => $fecha,
                    'descuento_codigo' => $descuento->codigo,
                ],
                [
                    'porcentaje' => $porcentaje,
                    'porcentaje_65' => $porcentaje_65,
                ]
            );
        } elseif ($descuento->codigo == 'SNP') {
            $this->actualizarDescuentoSNP($descuento);
        }
    }
    protected function actualizarDescuentoSNP(PlanDescuentoSp $descuento)
    {
        $descuentoSnp = Configuracion::find('descuento_snp');
        $valor = $descuentoSnp ? $descuentoSnp->valor : 0;

        // Obtener la fecha de inicio para el histórico
        $fecha = Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d');

        // Guardar en el histórico
        PlanDescuentoSPHistorico::updateOrCreate(
            [
                'fecha_inicio' => $fecha,
                'descuento_codigo' => $descuento->codigo,
            ],
            [
                'porcentaje' => $valor,
                'porcentaje_65' => $valor,
            ]
        );
    }
    protected function formatearPorcentaje($valor)
    {
        return floatval(str_replace(['%', ','], ['', '.'], trim($valor)));
    }
    public function cambiarFechaA($fecha)
    {
        $this->fecha_inicio = $fecha;
    }
    public function render()
    {
        $this->fechasRegistradas = PlanDescuentoSPHistorico::select('fecha_inicio')
            ->distinct()
            ->orderBy('fecha_inicio', 'desc')
            ->pluck('fecha_inicio')
            ->map(function ($fecha) {
                return Carbon::parse($fecha)->format('Y-m'); // Cambia el formato a Y-m
            })
            ->toArray();

        if ($this->fecha_inicio) {

            $this->descuentosSPHistorico = PlanDescuentoSPHistorico::whereDate('fecha_inicio', Carbon::createFromFormat('Y-m', $this->fecha_inicio)->startOfMonth()->format('Y-m-d'))->get();
        } else {
            $this->descuentosSPHistorico = null;
        }

        return view('livewire.configuracion-descuento-afp-component');
    }
}
