<?php

namespace App\Livewire;

use App\Models\Nutriente;
use App\Models\Producto;
use App\Models\ProductoNutriente;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TablaConcentracionComponent extends Component
{
    use LivewireAlert;
    public $listaNutrientes = [];
    public $listaFertilizantes = [];
    public $tableData = [];
    protected $listeners = ['guardarInformacionTablaConcentracion'];
    public function mount()
    {
        $this->listaNutrientes = Nutriente::get()->pluck('codigo')->toArray();
        $this->listaFertilizantes = Producto::where('categoria', 'fertilizante')->pluck('nombre_comercial')->toArray();
        // Mapear filas para Handsontable

        $productos = Producto::whereHas('nutrientes') // Solo los que tienen nutrientes
            ->with('nutrientes')                      // Cargar relación
            ->get();

        $this->tableData = $productos->map(function ($producto) {
            $fila = ['producto' => $producto->nombre_comercial];

            // Inicializar columnas de nutrientes en null
            foreach ($this->listaNutrientes as $codigo) {
                $fila[$codigo] = null;
            }

            // Rellenar con valores existentes
            foreach ($producto->nutrientes as $nutriente) {
                $fila[$nutriente->codigo] = $nutriente->pivot->porcentaje;
            }

            return $fila;
        })->toArray();
    }
    public function guardarInformacionTablaConcentracion($datos)
    {
        $errores = [];

        DB::beginTransaction();

        try {
            // Evita truncate, usa delete para no romper la transacción
            DB::table('producto_nutrientes')->delete();

            foreach ($datos as $fila) {
                $nombreProducto = trim($fila['producto'] ?? '');

                if ($nombreProducto === '') {
                    continue;
                }

                $productos = Producto::where('nombre_comercial', $nombreProducto)->get();

                if ($productos->count() === 0) {
                    $errores[] = "Producto '{$nombreProducto}' no encontrado.";
                    continue;
                }

                if ($productos->count() > 1) {
                    $errores[] = "Ambigüedad: más de un producto coincide con '{$nombreProducto}'.";
                    continue;
                }

                $producto = $productos->first();

                foreach ($fila as $clave => $valor) {
                    if ($clave === 'producto')
                        continue;

                    if ($valor === null || $valor === '')
                        continue;

                    $valor = str_replace(',', '.', $valor);

                    if (!is_numeric($valor)) {
                        $errores[] = "Valor inválido para {$clave} en producto '{$nombreProducto}': {$valor}";
                        continue;
                    }

                    $codigoNutriente = strtoupper(trim($clave));
                    $nutriente = Nutriente::find($codigoNutriente);

                    if (!$nutriente) {
                        $errores[] = "Nutriente '{$codigoNutriente}' no encontrado.";
                        continue;
                    }

                    ProductoNutriente::create([
                        'producto_id' => $producto->id,
                        'nutriente_codigo' => $nutriente->codigo,
                        'porcentaje' => round((float) $valor, 2),
                    ]);
                }
            }

            if (count($errores) > 0) {
                DB::rollBack();
                $this->alert('error', "Errores encontrados:\n" . implode("\n", $errores));
                return;
            }

            DB::commit();
            $this->alert('success', 'Datos guardados correctamente.');

        } catch (\Throwable $e) {
            // Evita llamar rollBack si no hay transacción activa
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->alert('error', 'Error inesperado: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.tabla-concentracion-component');
    }
}
