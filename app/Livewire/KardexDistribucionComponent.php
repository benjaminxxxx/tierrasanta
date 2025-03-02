<?php

namespace App\Livewire;

use App\Exports\KardexProductoExport;
use App\Models\AlmacenProductoSalida;
use App\Models\CompraProducto;
use App\Models\CompraSalidaStock;
use App\Models\Empresa;
use App\Models\Kardex;
use App\Models\KardexProducto;
use App\Models\Producto;
use Carbon\Carbon;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Storage;

class KardexDistribucionComponent extends Component
{
    use LivewireAlert;
    public $kardexProductoId;
    public $kardexId;
    public $grupoNegro = [];
    public $grupoBlanco = [];
    public $grupoLibre = [];
    public $movimientos = [];
    public $selectedItems = [];
    public $codigoExistenciaNegro;
    public $stockInicialNegro;
    public $costoInicialNegro;
    public $codigoExistenciaBlanco;
    public $stockInicialBlanco;
    public $costoInicialBlanco;
    public $kardex;
    public $kardexProductoBlanco;
    public $kardexProductoNegro;
    public $esCombustible = false;
    public $totalCompras = 0;
    public $totalSalidas = 0;
    public $kardexLista = [];
    public $empresa;
    protected $listeners = ['eliminacionConfirmar','actualizarCompraProductos'];
    public function mount()
    {
        if ($this->kardexId) {
            $this->kardex = Kardex::find($this->kardexId);
        }
        $this->empresa = Empresa::first();
        $this->reevaluar();
        $this->listarInformacionBlanco();
        $this->listarInformacionNegro();
    }
    public function actualizarCompraProductos($data)
    {
        $this->reevaluar();
        $this->listarInformacionBlanco();
        $this->listarInformacionNegro();
    }
    public function listarKardex($tipoKardex, $kardexProducto)
    {

        $this->totalCompras = 0;
        $this->totalSalidas = 0;

        $this->esCombustible = Producto::esCombustible($this->kardexProductoId);
        $informacion = $tipoKardex == 'negro' ? $this->grupoNegro : $this->grupoBlanco;

        $this->kardexLista = [];

        $this->kardexLista[] = [
            'tipo' => 'a',
            'fecha' => $this->kardex->fecha_inicial,
            'tabla10' => '',
            'serie' => '',
            'numero' => '',
            'tipo_operacion' => 16, //SALDO INICIAL
            'entrada_cantidad' => $kardexProducto->stock_inicial,
            'entrada_costo_unitario' => $kardexProducto->costo_unitario,
            'entrada_costo_total' => $kardexProducto->costo_total,
            'salida_cantidad' => '',
            'salida_lote' => '',
            'salida_maquinaria' => '',
            'salida_costo_unitario' => '',
            'salida_costo_total' => '',
            'saldofinal_cantidad' => '',
            'saldofinal_costo_unitario' => '',
            'saldofinal_costo_total' => '',
        ];

        foreach ($informacion as $item) {
            $data = json_decode($item['data'], true); // Convertir JSON a array

            if ($item['tipo'] === 'compra') {
                $this->kardexLista[] = [
                    'tipo' => 'compra',
                    'compra_id' => $data['compra_id'],
                    'fecha' => $item['fecha'],
                    'tabla10' => $data['tipo_compra_codigo'],
                    'serie' => $data['serie'],
                    'numero' => $data['numero'],
                    'tipo_operacion' => 2, // Código de operación para compra
                    'entrada_cantidad' => $data['stock'],
                    'entrada_costo_unitario' => $data['total'] / $data['stock'],
                    'entrada_costo_total' => $data['total'],
                    'salida_cantidad' => '',
                    'salida_lote' => '',
                    'salida_maquinaria' => '',
                    'salida_costo_unitario' => '',
                    'salida_costo_total' => '',
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];
                $this->totalCompras++;
            } elseif ($item['tipo'] === 'salida') {
                $this->kardexLista[] = [
                    'tipo' => 'salida',
                    'salida_id' => $data['salida_id'],
                    'fecha' => $item['fecha'],
                    'tabla10' => '',
                    'serie' => '',
                    'numero' => '',
                    'tipo_operacion' => 10, // SALIDA A PRODUCCIÓN
                    'entrada_cantidad' => '',
                    'entrada_costo_unitario' => '',
                    'entrada_costo_total' => '',
                    'salida_cantidad' => $data['cantidad'],
                    'salida_lote' => $data['campo_nombre'],
                    'salida_maquinaria' => $data['costo_por_kg'] ?? '',
                    'salida_costo_unitario' => $data['costo_por_kg'] ?? '',
                    'salida_costo_total' => $data['total_costo'] ?? '',
                    'saldofinal_cantidad' => '',
                    'saldofinal_costo_unitario' => '',
                    'saldofinal_costo_total' => '',
                ];
                
                $this->totalSalidas++;
            }
        }

        // Ordenar por fecha y tipo
        $fecha = array_column($this->kardexLista, 'fecha');
        $tipo = array_column($this->kardexLista, 'tipo');

        array_multisort($fecha, SORT_ASC, $tipo, SORT_ASC, $this->kardexLista);
      
    }
    protected function obtenerDatosKardex($tipoKardex, $kardexProducto)
    {
        $tieneTipo = $kardexProducto->producto->tabla5;
        if (!$tieneTipo) {
            return $this->alert('error', 'El producto no tiene un tipo, editar el producto.');
        }

        $periodo = Carbon::parse($this->kardex->fecha_inicial)->format('Y');

        return [
            'kardexId' => $this->kardexId,
            'productoId' => $this->kardexProductoId,
            'esCombustible' => $this->esCombustible,
            'kardexLista' => $this->kardexLista,
            'informacionHeader' => [
                'periodo' => $periodo,
                'ruc' => $this->empresa->ruc,
                'razon_social' => $this->empresa->razon_social,
                'establecimiento' => $this->empresa->establecimiento,
                'codigo_existencia' => $kardexProducto->codigo_existencia,
                'tipo' => $kardexProducto->producto->tabla5->codigo . ' - ' . $kardexProducto->producto->tabla5->descripcion,
                'descripcion' => $kardexProducto->producto->nombre_comercial,
                'codigo_unidad_medida' => $kardexProducto->producto->tabla6->codigo . ' - ' . $kardexProducto->producto->tabla6->descripcion,
                'metodo_valuacion' => 'PROMEDIO',
            ],
        ];
    }
    public function generarKardex($tipoKardex)
    {
        try {
            if (!$this->kardex) {
                throw new Exception("El Kardex no existe.");
            }
            if (!$this->empresa) {
                throw new Exception("No hay datos de empresa registrada.");
            }
            if ($tipoKardex == 'negro' && !$this->kardexProductoNegro) {
                throw new Exception("Debe actualizar los valores iniciales del Kardex Negro.");
            }
            if ($tipoKardex == 'blanco' && !$this->kardexProductoBlanco) {
                throw new Exception("Debe actualizar los valores iniciales del Kardex Blanco.");
            }

            $kardexProducto = ($tipoKardex == 'negro') ? $this->kardexProductoNegro : $this->kardexProductoBlanco;
            $this->listarKardex($tipoKardex, $kardexProducto);
            $data = $this->obtenerDatosKardex($tipoKardex, $kardexProducto);

            $filePath = 'kardex/' . date('Y-m') . '/' .
                $kardexProducto->codigo_existencia . '_' . $tipoKardex . '_' .
                Str::slug($kardexProducto->producto->nombre_completo) .
                '.xlsx';
            Excel::store(new KardexProductoExport($data), $filePath, 'public');

            $kardexProducto->file = $filePath;
            $kardexProducto->save();
            $this->procesarFile($filePath, $kardexProducto->codigo_existencia);
            $this->relacionarSalidaEnCompra($kardexProducto);
            $this->reevaluar();
            $this->alert('success', 'Kardex generado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Error en Procesar Archivo:' . $th->getMessage(), [

                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
        }
    }
    public function relacionarSalidaEnCompra($kardexProducto)
    {
        $stockInicialDisponible = (float) $kardexProducto->stock_inicial;
        $colaCompras = []; // Mantiene el orden cronológico
        $kardexProducto->compraSalidaStock()->delete();
        $informacion = $kardexProducto->tipo_kardex == 'negro' ? $this->grupoNegro : $this->grupoBlanco;

        foreach ($informacion as $item) {
            $data = json_decode($item['data'], true);

            // **Registrar compras en la cola**
            if ($item['tipo'] == 'compra') {
                $colaCompras[] = [ // Se usa array indexado para preservar orden
                    'compra_id' => $data['compra_id'],
                    'estado' => 'enStock',
                    'stock' => (float) $data['stock'],
                    'stock_usado' => 0
                ];
            }

            // **Procesar salida**
            if ($item['tipo'] == 'salida') {
                $stockUtilizado = (float) $data['cantidad'];

                // **1️⃣ Usar primero el stock inicial**
                if ($stockInicialDisponible > 0) {
                    if ($stockInicialDisponible >= $stockUtilizado) {
                        $stockInicialDisponible -= $stockUtilizado;
                        $almacenProductoSalida = AlmacenProductoSalida::find($data['salida_id']);
                        if ($almacenProductoSalida) {
                            $almacenProductoSalida->update([
                                'cantidad_kardex_producto_id' => $kardexProducto->id,
                                'cantidad_stock_inicial' => $stockUtilizado
                            ]);
                        }
                        $stockUtilizado = 0;
                    } else {
                        $almacenProductoSalida = AlmacenProductoSalida::find($data['salida_id']);
                        if ($almacenProductoSalida) {
                            $almacenProductoSalida->update([
                                'cantidad_kardex_producto_id' => $kardexProducto->id,
                                'cantidad_stock_inicial' => $stockInicialDisponible
                            ]);
                        }
                        $stockUtilizado -= $stockInicialDisponible;
                        $stockInicialDisponible = 0; // Se agotó el stock inicial
                    }
                }

                // **2️⃣ Si aún falta stock, usar compras en orden FIFO**
                if ($stockUtilizado > 0) {
                    foreach ($colaCompras as $key => &$compra) { // Usamos referencia para modificar directamente
                        if ($compra['estado'] === 'enStock') {
                            $stockDisponibleCompra = $compra['stock'] - $compra['stock_usado'];
                            $usoStockActual = 0;

                            if ($stockDisponibleCompra >= $stockUtilizado) {
                                // Se cubre completamente con esta compra
                                $usoStockActual = $stockUtilizado;
                                $compra['stock_usado'] += $stockUtilizado;
                                $stockUtilizado = 0;
                            } else {
                                // Se usa todo el stock disponible de esta compra
                                $usoStockActual = $stockDisponibleCompra;
                                $stockUtilizado -= $stockDisponibleCompra;
                                $compra['stock_usado'] = $compra['stock'];
                            }

                            // **Actualizar estado si la compra se agotó**
                            if ($compra['stock_usado'] >= $compra['stock']) {
                                $compra['estado'] = 'terminado';

                                // **Actualizar en la base de datos**
                                CompraProducto::find($compra['compra_id'])->update([
                                    'fecha_termino' => $item['fecha']
                                ]);
                            }

                            // **Registrar relación en la BD** con el stock utilizado en esta iteración
                            CompraSalidaStock::create([
                                'compra_producto_id' => $compra['compra_id'],
                                'salida_almacen_id' => $data['salida_id'],
                                'stock' => $usoStockActual, // 🔥 **CORREGIDO**
                                'kardex_producto_id' => $kardexProducto->id
                            ]);

                            if ($stockUtilizado == 0) {
                                break; // Se cubrió toda la salida
                            }
                        }
                    }
                    unset($compra); // Evitar problemas con referencias de PHP
                }

                // **3️⃣ Limpiar compras terminadas después de recorrer todo**
                $colaCompras = array_filter($colaCompras, fn($compra) => $compra['estado'] !== 'terminado');
            }
        }
    }

    public function procesarFile($filePath, $codigoExistencia)
    {
        $fullPath = Storage::disk('public')->path($filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getSheetByName($codigoExistencia);
        if (!$sheet) {
            throw new Exception("El Excel no tiene una hoja llamada: " . $codigoExistencia);
        }

        $rows = array_slice($sheet->toArray(), 16); // Elimina las primeras 16 filas no relevantes

        $index = 0;
        foreach ($this->kardexLista as &$item) {
            if (!isset($rows[$index])) {
                break; // Si ya no hay más filas en el Excel, terminamos
            }

            if ($item['tipo'] === 'salida') {

                $item['salida_costo_unitario'] = (float) str_replace(',', '', $rows[$index + 1][10]);
                $item['salida_costo_total'] = (float) str_replace(',', '', $rows[$index + 1][11]);
                $item['saldofinal_cantidad'] = (float) str_replace(',', '', $rows[$index + 1][12]);
                $item['saldofinal_costo_unitario'] = (float) str_replace(',', '', $rows[$index + 1][13]);
                $item['saldofinal_costo_total'] = (float) str_replace(',', '', $rows[$index + 1][14]);
            }
            if ($item['tipo'] === 'compra' || $item['tipo'] === 'salida') {
                $index++;
            }
        }
        foreach ($this->kardexLista as $item2) {
            if ($item2['tipo'] === 'salida') {
                AlmacenProductoSalida::where('id', $item2['salida_id'])->update([
                    'costo_por_kg' => $item2['salida_costo_unitario'],
                    'total_costo' => $item2['salida_costo_total'],
                ]);
            }
        }

    }
    public function listarInformacionBlanco()
    {
        if (!$this->kardex) {
            return;
        }
        $kardexProductoBlanco = $this->kardex->productos()
            ->where('producto_id', $this->kardexProductoId)
            ->where('tipo_kardex', 'blanco')
            ->first();

        if ($kardexProductoBlanco) {
            $this->kardexProductoBlanco = $kardexProductoBlanco;
            $this->codigoExistenciaBlanco = $kardexProductoBlanco->codigo_existencia;
            $this->stockInicialBlanco = $kardexProductoBlanco->stock_inicial;
            $this->costoInicialBlanco = $kardexProductoBlanco->costo_total;
        } else {
            $this->reset(['kardexProductoBlanco', 'codigoExistenciaBlanco', 'stockInicialBlanco', 'costoInicialBlanco']);
        }
    }
    public function listarInformacionNegro()
    {
        if (!$this->kardex) {
            return;
        }
        $kardexProductoNegro = $this->kardex->productos()
            ->where('producto_id', $this->kardexProductoId)
            ->where('tipo_kardex', 'negro')
            ->first();

        if ($kardexProductoNegro) {
            $this->kardexProductoNegro = $kardexProductoNegro;
            $this->codigoExistenciaNegro = $kardexProductoNegro->codigo_existencia;
            $this->stockInicialNegro = $kardexProductoNegro->stock_inicial;
            $this->costoInicialNegro = $kardexProductoNegro->costo_total;
        } else {
            $this->reset(['kardexProductoNegro', 'codigoExistenciaNegro', 'stockInicialNegro', 'costoInicialNegro']);
        }
    }
    public function corroborarGrupo($grupo, $nombreGrupo)
    {
        $stockInicial = 0; // Puedes modificarlo si es necesario
        $stockActual = $stockInicial;

        foreach ($grupo as $index => $movimiento) {
            $data = json_decode($movimiento['data']);

            if ($movimiento['tipo'] === 'compra') {
                $stockActual += $data->stock;
            } elseif ($movimiento['tipo'] === 'salida') {
                $stockActual -= $data->cantidad;
            }

            if ($stockActual < 0) {
                return $this->alert('error', "Error en el grupo {$nombreGrupo}, índice {$index}: el stock no puede ser negativo.");

            }
        }

        $this->alert('success', "Revisión completada en {$nombreGrupo}, no hay errores en el stock.");
    }
    public function corroborarNegro()
    {
        $this->corroborarGrupo($this->grupoNegro, 'Negro');
    }

    public function corroborarBlanco()
    {
        $this->corroborarGrupo($this->grupoBlanco, 'Blanco');
    }

    public function corroborarLibre()
    {
        $this->corroborarGrupo($this->grupoLibre, 'Libre');
    }

    public function reevaluar()
    {
        if ($this->kardexProductoId && $this->kardexId) {
            $kardex = Kardex::find($this->kardexId);
            if ($kardex) {

                $grupoBlanco = [];
                $grupoNegro = [];
                $grupoLibre = [];

                // Procesar compras
                foreach ($kardex->compras($this->kardexProductoId)->get() as $compra) {
                    $data = [
                        'tipo' => 'compra',
                        'fecha' => $compra->fecha_compra,
                        'indice' => null, // No aplica en compras
                        'tipo_kardex' => $compra->tipo_kardex ?? 'libre',
                        'data' => json_encode([
                            'compra_id' => $compra->id,
                            'producto_id' => $compra->producto_id,
                            'serie' => $compra->serie,
                            'numero' => $compra->numero,
                            'tipo_compra_codigo' => $compra->tipo_compra_codigo,
                            'total' => $compra->total,
                            'stock' => $compra->stock,
                        ])
                    ];
                    if ($compra->tipo_kardex == 'blanco') {
                        $grupoBlanco[] = $data;
                    } elseif ($compra->tipo_kardex == 'negro') {
                        $grupoNegro[] = $data;
                    } else {
                        $grupoLibre[] = $data;
                    }
                }

                // Procesar salidas
                foreach ($kardex->salidas($this->kardexProductoId)->get() as $salida) {
                    $data = [
                        'tipo' => 'salida',
                        'fecha' => $salida->fecha_reporte,
                        'indice' => $salida->indice,
                        'tipo_kardex' => $salida->tipo_kardex ?? 'libre',
                        'data' => json_encode([
                            'salida_id' => $salida->id,
                            'producto_id' => $salida->producto_id,
                            'campo_nombre' => $salida->es_combustible? $salida->maquina_nombre : $salida->campo_nombre,
                            'costo_por_kg' => $salida->costo_por_kg,
                            'total_costo' => $salida->total_costo,
                            'cantidad' => $salida->cantidad,
                        ])
                    ];
                    if ($salida->tipo_kardex == 'blanco') {
                        $grupoBlanco[] = $data;
                    } elseif ($salida->tipo_kardex == 'negro') {
                        $grupoNegro[] = $data;
                    } else {
                        $grupoLibre[] = $data;
                    }
                }

                $this->ordenarGrupo($grupoBlanco);
                $this->ordenarGrupo($grupoNegro);
                $this->ordenarGrupo($grupoLibre);

                $this->grupoBlanco = $grupoBlanco;
                $this->grupoNegro = $grupoNegro;
                $this->grupoLibre = $grupoLibre;

                $movimientosGenerado = [];

                $movimientos = array_merge($grupoBlanco, $grupoNegro, $grupoLibre);

                // Ordenar el array combinado
                usort($movimientos, function ($a, $b) {
                    if ($a['fecha'] == $b['fecha']) {
                        if ($a['tipo'] == $b['tipo']) {
                            return $a['indice'] <=> $b['indice'];
                        }
                        return ($a['tipo'] == 'compra') ? -1 : 1;
                    }
                    return strtotime($a['fecha']) <=> strtotime($b['fecha']);
                });

                // Aplanar los datos
                $movimientosGenerado = [];
                foreach ($movimientos as $movimiento) {
                    $fecha = $movimiento['fecha'];
                    $tipoKardex = $movimiento['tipo_kardex'];

                    // Buscar una fila con la misma fecha que tenga espacio para este tipo_kardex
                    $insertado = false;
                    foreach ($movimientosGenerado as &$fila) {
                        if ($fila['fecha'] == $fecha && !isset($fila[$tipoKardex])) {
                            $fila[$tipoKardex] = $movimiento;
                            $insertado = true;
                            break;
                        }
                    }

                    // Si no se encontró espacio, crear una nueva fila
                    if (!$insertado) {
                        $movimientosGenerado[] = [
                            'fecha' => $fecha,
                            'blanco' => ($tipoKardex == 'blanco') ? $movimiento : null,
                            'negro' => ($tipoKardex == 'negro') ? $movimiento : null,
                            'libre' => ($tipoKardex == 'libre') ? $movimiento : null,
                        ];
                    }
                }
                $this->movimientos = $movimientosGenerado;

            }
        }
    }
    public function actualizarInformacionBlanco()
    {
        $this->validate([
            "codigoExistenciaBlanco" => "required",
            "stockInicialBlanco" => "required",
            "costoInicialBlanco" => "required",
        ], [
            "codigoExistenciaBlanco.required" => "El código de existencia es requerido",
            "stockInicialBlanco.required" => "El stock inicial es requerido",
            "costoInicialBlanco.required" => "El costo total es requerido"
        ]);

        try {
            $kardeProducto = $this->kardex->productos()
                ->where('producto_id', $this->kardexProductoId)
                ->where('tipo_kardex', 'blanco')
                ->first();

            $stockInicial = (float) $this->stockInicialBlanco;
            $costoInicial = (float) $this->costoInicialBlanco;
            $costoUnitario = $stockInicial != 0 ? ($costoInicial / $stockInicial) : 0;

            if ($kardeProducto) {
                $kardeProducto->update([
                    'codigo_existencia' => mb_strtoupper($this->codigoExistenciaBlanco),
                    'stock_inicial' => $stockInicial,
                    'costo_total' => $costoInicial,
                    'costo_unitario' => $costoUnitario
                ]);
            } else {
                KardexProducto::create([
                    'kardex_id' => $this->kardex->id,
                    'producto_id' => $this->kardexProductoId,
                    'tipo_kardex' => 'blanco',
                    'codigo_existencia' => mb_strtoupper($this->codigoExistenciaBlanco),
                    'stock_inicial' => $stockInicial,
                    'costo_total' => $costoInicial,
                    'costo_unitario' => $costoUnitario,
                    'metodo_valuacion' => 'promedio',
                ]);
            }
            $this->listarInformacionBlanco();
            $this->alert('success', 'Información actualizada.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error el actualizar la información.');
        }
    }
    public function actualizarInformacionNegro()
    {

        $this->validate([
            "codigoExistenciaNegro" => "required",
            "stockInicialNegro" => "required",
            "costoInicialNegro" => "required",
        ], [
            "codigoExistenciaNegro.required" => "El código de existencia es requerido",
            "stockInicialNegro.required" => "El stock inicial es requerido",
            "costoInicialNegro.required" => "El costo total es requerido"
        ]);

        try {
            $kardeProducto = $this->kardex->productos()
                ->where('producto_id', $this->kardexProductoId)
                ->where('tipo_kardex', 'negro')
                ->first();

            $stockInicial = (float) $this->stockInicialNegro;
            $costoInicial = (float) $this->costoInicialNegro;
            $costoUnitario = $stockInicial != 0 ? ($costoInicial / $stockInicial) : 0;

            if ($kardeProducto) {
                $kardeProducto->update([
                    'codigo_existencia' => mb_strtoupper($this->codigoExistenciaNegro),
                    'stock_inicial' => $stockInicial,
                    'costo_total' => $costoInicial,
                    'costo_unitario' => $costoUnitario
                ]);
            } else {
                KardexProducto::create([
                    'kardex_id' => $this->kardex->id,
                    'producto_id' => $this->kardexProductoId,
                    'tipo_kardex' => 'negro',
                    'codigo_existencia' => mb_strtoupper($this->codigoExistenciaNegro),
                    'stock_inicial' => $stockInicial,
                    'costo_total' => $costoInicial,
                    'costo_unitario' => $costoUnitario,
                    'metodo_valuacion' => 'promedio',
                ]);
            }
            $this->listarInformacionNegro();
            $this->alert('success', 'Información actualizada.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error el actualizar la información.');
        }
    }
    public function ordenarGrupo(&$grupo)
    {
        usort($grupo, function ($a, $b) {
            if ($a['fecha'] == $b['fecha']) {
                if ($a['tipo'] == $b['tipo']) {
                    return $a['indice'] <=> $b['indice'];
                }
                return ($a['tipo'] == 'compra') ? -1 : 1;
            }
            return strtotime($a['fecha']) <=> strtotime($b['fecha']);
        });
    }
    public function cambiarSalidaA($tipo, $salidaId)
    {
        try {
            $salida = AlmacenProductoSalida::findOrFail($salidaId);
            $salida->update([
                'tipo_kardex' => $tipo
            ]);
            $this->reevaluar();
        } catch (\Throwable $th) {
            return $this->alert('error', 'La salida ya no existe.');
        }
    }
    public function cambiarCompraA($tipo, $compraId)
    {
        try {
            $compra = CompraProducto::findOrFail($compraId);
            $compra->update([
                'tipo_kardex' => $tipo
            ]);
            $this->reevaluar();
        } catch (\Throwable $th) {
            return $this->alert('error', 'La salida ya no existe.');
        }
    }
    public function eliminarComprasSalidas($tipoKardex)
    {

        $this->alert('question', '¿Está seguro que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'eliminacionConfirmar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'tipoKardex' => $tipoKardex,
            ],
        ]);
    }
    public function eliminacionConfirmar($data)
    {
        $tipoKardex = $data['tipoKardex'];

        $kardexProducto = $tipoKardex=='negro'?$this->kardexProductoNegro:$this->kardexProductoBlanco;

        if(!$kardexProducto){
            return $this->alert('error',"Debe haber un Kardex de tipo {$tipoKardex} para saber de que fecha a que fecha eliminar.");
        }

        $compras = $kardexProducto->compras()->get();
        $salidas = $kardexProducto->salidas()->get();

        if($compras){
            foreach ($compras as $compra) {
                $compra->almacenSalida()->delete();
                $compra->delete();
            }
        }
        if($salidas){
            foreach ($salidas as $salida) {
                $salida->compraStock()->delete();
                $salida->delete();
            }
        }
        $this->reevaluar();
        $this->alert("success", "Registros eliminados correctamente.");
        $this->registroIdEliminar = null;
    }
    public function render()
    {
        return view('livewire.kardex-distribucion-component');
    }
}
