<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\DescuentoSP;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ConfiguracionDescuentoAfpComponent extends Component
{
    use LivewireAlert;
    public $descuentosSP;
    public $descuentos;
    public $descuentos65;
    public $informacion;
    public function mount()
    {
        $this->informacion = 'HABITAT	1,47%	1,25%	1,70%	10,00%	11 981,55
INTEGRA	1,55%	0,78%	1,70%	10,00%	11 981,55
PRIMA	1,60%	1,25%	1,70%	10,00%	11 981,55
PROFUTURO	1,69%	1,20%	1,70%	10,00%	11 981,55';
        $this->cargarDescuentos();
    }
    protected function cargarDescuentos()
    {
        $this->descuentosSP = DescuentoSP::orderBy('orden', 'asc')->get();
        $this->descuentos = $this->descuentosSP->pluck('porcentaje', 'codigo')->toArray();
        $this->descuentos65 = $this->descuentosSP->pluck('porcentaje_65', 'codigo')->toArray();
    }
    public function limpiarMontos()
    {
        try {
            // Limpia los montos estableciendo los porcentajes en 0
            DescuentoSP::query()->update([
                'porcentaje' => 0,
                'porcentaje_65' => 0,
            ]);

            $this->cargarDescuentos();

            // Mensaje de éxito
            $this->alert('success', 'Los montos han sido limpiados exitosamente.');
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

            $this->cargarDescuentos();
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
                $comisionSobreSaldo = $this->formatearPorcentaje($columnas[2]);
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
    protected function actualizarDescuento(DescuentoSP $descuento, array $valoresDescuentos)
    {
        if (isset($valoresDescuentos[$descuento->referencia])) {
            $valorDescuento = $valoresDescuentos[$descuento->referencia];

            if ($descuento->tipo == 'Flujo') {
                $descuento->porcentaje = $valorDescuento['flujo'];
                $descuento->porcentaje_65 = $valorDescuento['flujo_65'];
            } elseif ($descuento->tipo == 'Mixta') {
                $descuento->porcentaje = $valorDescuento['mixta'];
                $descuento->porcentaje_65 = $valorDescuento['mixta_65'];
            }
        } elseif ($descuento->codigo == 'SNP') {
            $this->actualizarDescuentoSNP($descuento);
        }

        $descuento->save();
    }
    protected function actualizarDescuentoSNP(DescuentoSP $descuento)
    {
        $descuentoSnp = Configuracion::find('descuento_snp');
        $valor = $descuentoSnp ? $descuentoSnp->valor : 0;
        $descuento->porcentaje = $valor;
        $descuento->porcentaje_65 = $valor;
    }
    protected function formatearPorcentaje($valor)
    {
        return floatval(str_replace(['%', ','], ['', '.'], trim($valor)));
    }
    public function render()
    {
        return view('livewire.configuracion-descuento-afp-component');
    }
}
