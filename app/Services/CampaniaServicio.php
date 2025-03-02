<?php

namespace App\Services;

use App\Exports\CampoConsumoExport;
use App\Models\Actividad;
use App\Models\AlmacenProductoSalida;
use App\Models\CampoCampania;
use App\Models\CamposCampaniasConsumo;
use App\Models\CategoriaProducto;
use App\Models\ContabilidadCostoDetalle;
use App\Models\ResumenConsumoProductos;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use App\Support\ExcelHelper;
use App\Support\CalculoHelper;

class CampaniaServicio
{
    public $campoCampaniaId;
    public $campoCampania;
    public function __construct($campoCampaniaId = null)
    {
        $this->campoCampaniaId = $campoCampaniaId;
        if ($this->campoCampaniaId) {
            $this->campoCampania = CampoCampania::find($this->campoCampaniaId);
            if (!$this->campoCampania) {
                throw new Exception("La campaña no existe.");
            }
        }
    }
    /**
     * Actualiza los Gastos y Consumos de una determinada campaña
     * @param int $campoCampaniaId
     */
    public function actualizarGastosyConsumos()
    {
        $this->campoCampania->update([
            'gasto_planilla' => $this->gastoPlanilla(),
            'gasto_cuadrilla' => $this->gastoCuadrilla()
        ]);

        $this->actualizarConsumo();
        $this->campoCampania->refresh();
        $this->generarBddMensual();
    }
    public function generarBddMensual()
    {

        if (!$this->campoCampania) {
            throw new Exception("La Campañia no Existe");
        }
        // Cargar la plantilla desde public/templates
        $spreadsheet = ExcelHelper::cargarPlantilla('bdd_campo.xlsx');
        $hoja = $spreadsheet->getSheetByName('FORMATO');

        if (!$hoja) {
            throw new Exception("No se ha configurado un formato para el documento a exportar.");
        }

        $nuevoNombre = mb_strtoupper(Str::slug($this->campoCampania->campo, '_')); // Reemplaza espacios con "_"
        $hoja->setTitle($nuevoNombre);

        $hoja->setCellValue("D1", "RESUMEN CAMPO: {$nuevoNombre}");

        $informacionPlanilla = $this->generarInformacionPlanilla();
        $informacionCuadrilla = $this->generarInformacionCuadrilla();
        $informacionConsumo = $this->generarInformacionConsumo();
        $informacionCostosFijosOperativos = $this->generarCostosFijosOperativos();

        $informacionCombinada = array_merge($informacionPlanilla, $informacionCuadrilla, $informacionConsumo,$informacionCostosFijosOperativos);

        // Ordenar por fecha
        usort($informacionCombinada, function ($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });

        $fila = 6;

        foreach ($informacionCombinada as $dato) {
            $hoja->setCellValue("A{$fila}", $dato['fecha'] ?? '');
            $hoja->setCellValue("B{$fila}", $dato['tipo_cambio'] ?? '');
            $hoja->setCellValue("C{$fila}", $dato['campania'] ?? '');
            $hoja->setCellValue("D{$fila}", $dato['horas'] ?? '');
            $hoja->setCellValue("E{$fila}", $dato['planilla_nombre'] ?? '');
            $hoja->setCellValue("F{$fila}", $dato['planilla_h'] ?? '');
            $hoja->setCellValue("G{$fila}", $dato['planilla_m'] ?? '');
            $hoja->setCellValue("H{$fila}", $dato['cuadrilla_fija_cantidad'] ?? '');
            $hoja->setCellValue("I{$fila}", $dato['cuadrilla_fija_costo'] ?? '');
            $hoja->setCellValue("J{$fila}", $dato['cuadrilla_cantidad'] ?? '');
            $hoja->setCellValue("K{$fila}", $dato['cuadrilla_costo'] ?? '');
            $hoja->setCellValue("L{$fila}", $dato['mano_obra'] ?? '');
            $hoja->setCellValue("M{$fila}", $dato['cantidad_jornales'] ?? '');
            $hoja->setCellValue("N{$fila}", $dato['costo'] ?? '');

            $hoja->setCellValue("O{$fila}", $dato['maquinaria'] ?? '');
            $hoja->setCellValue("P{$fila}", $dato['maquinaria_costo'] ?? '');

            $hoja->setCellValue("Q{$fila}", $dato['consumo_fertilizante_cantidad'] ?? '');
            $hoja->setCellValue("R{$fila}", $dato['consumo_fertilizante_nombre_comercial'] ?? '');
            $hoja->setCellValue("S{$fila}", $dato['consumo_fertilizante_orden_compra'] ?? '');
            $hoja->setCellValue("T{$fila}", $dato['consumo_fertilizante_tienda_comercial'] ?? '');
            $hoja->setCellValue("U{$fila}", $dato['consumo_fertilizante_factura'] ?? '');
            $hoja->setCellValue("V{$fila}", $dato['consumo_fertilizante_costo'] ?? '');

            $hoja->setCellValue("W{$fila}", $dato['consumo_pesticida_cantidad'] ?? '');
            $hoja->setCellValue("X{$fila}", $dato['consumo_pesticida_nombre_comercial'] ?? '');
            $hoja->setCellValue("Y{$fila}", $dato['consumo_pesticida_orden_compra'] ?? '');
            $hoja->setCellValue("Z{$fila}", $dato['consumo_pesticida_tienda_comercial'] ?? '');
            $hoja->setCellValue("AA{$fila}", $dato['consumo_pesticida_factura'] ?? '');
            $hoja->setCellValue("AB{$fila}", $dato['consumo_pesticida_costo'] ?? '');

            $hoja->setCellValue("AC{$fila}", $dato['costo_fijo'] ?? '');
            $hoja->setCellValue("AD{$fila}", $dato['costo_fijo_costo'] ?? '');
            $hoja->setCellValue("AE{$fila}", $dato['costo_operativo'] ?? '');
            $hoja->setCellValue("AF{$fila}", $dato['costo_operativo_costo'] ?? '');

            $fila++; // Avanzar a la siguiente fila
        }


        // Definir ruta de almacenamiento en "storage/app/public/reporte/..."
        $folderPath = 'reporte/' . date('Y-m');
        $fileName = 'BDD_CAMPAÑA_' . mb_strtoupper(Str::slug($this->campoCampania->nombre_campania)) .'_CAMPO_' . mb_strtoupper(Str::slug($this->campoCampania->campo)). '.xlsx';
        $filePath = $folderPath . '/' . $fileName;

        // Crear carpeta si no existe
        Storage::disk('public')->makeDirectory($folderPath);

        // Guardar el archivo en storage/app/public/reporte/YYYY-MM/
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('public')->path($filePath));

        $this->campoCampania->update([
            'gasto_resumen_bdd_file' => $filePath
        ]);


    }
    public function generarInformacionPlanilla()
    {
        $informacion = [];

        $gasto_planilla_file = $this->campoCampania->gasto_planilla_file;
        if ($gasto_planilla_file) {

            $hoja = ExcelHelper::cargarHoja('public', $gasto_planilla_file, 'GASTO PLANILLA');

            $table = $hoja->getTableByName('GASTO_PLANILLA');

            if (!$table) {
                throw new Exception("No se encontró la tabla GASTO_PLANILLA.");
            }

            // Obtener el rango de la tabla (ejemplo: "A1:O20")
            $tableRange = $table->getRange();

            // Extraer los datos del rango de la tabla
            $data = $hoja->rangeToArray($tableRange, null, true, false, true);

            if (!$data || count($data) < 2) {
                throw new Exception("No hay datos suficientes en la tabla.");
            }
            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

            // Reestructurar los datos con claves semánticas y resetear índices
            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

            // Mostrar datos reestructurados           
            foreach ($data as $fila) {
                $cantidadJornales = CalculoHelper::calcularJornales($fila['total_de_horas']);
                $informacion[] = [
                    'fecha' => $fila['fecha'],
                    'tipo_cambio' => 1,
                    'campania' => $fila['campana'],
                    'horas' => $fila['total_de_horas'],
                    'planilla_nombre' => $fila['empleado'],
                    'planilla_h' => '',
                    'planilla_m' => '',
                    'cuadrilla_fija_cantidad' => '',
                    'cuadrilla_fija_costo' => '',
                    'cuadrilla_cantidad' => '',
                    'cuadrilla_costo' => '',
                    'mano_obra' => $fila['labor'],
                    'cantidad_jornales' => $cantidadJornales,
                    'costo' => $fila['gasto_total'],
                ];
            }
        }

        return $informacion;
    }
    public function generarCostosFijosOperativos()
    {
        $informacion = [];
        $fecha_inicio = $this->campoCampania->fecha_inicio;
        $fecha_fin = $this->campoCampania->fecha_fin;
        $campo = $this->campoCampania->campo;

        $registros = ContabilidadCostoDetalle::where('campo', $campo)
            ->whereHas('registroCosto', function ($query) use ($fecha_inicio, $fecha_fin) {
                $query->whereDate('fecha', '>=', $fecha_inicio);
                if ($fecha_fin) {
                    $query->whereDate('fecha', '<=', $fecha_fin);
                }
            })
            ->with(['registroCosto', 'registroCosto.tipoCosto']) // Evita el N+1
            ->get();

        if ($registros->isEmpty()) { // Mejor que !$registros, ya que $registros siempre será una colección
            return $informacion;
        }

        foreach ($registros as $registro) {
            $registroCosto = $registro->registroCosto;
            $tipo = $registroCosto->tipoCosto->tipo_costo ?? 'Sin tipo de costo';
            $nombreCosto = $registroCosto->tipoCosto->nombre_costo;
            $fecha = $registroCosto->fecha;
            $campania = $this->campoCampania->nombre_campania;
            $valor = $registroCosto->valor;

            switch ($tipo) {
                case 'fijo':
                    $informacion[] = [
                        'fecha' => $fecha,
                        'tipo_cambio' => 1,
                        'campania' => $campania,
                        'costo_fijo' => $nombreCosto,
                        'costo_fijo_costo' => $valor
                    ];
                    break;
                case 'operativo':
                default:
                    $informacion[] = [
                        'fecha' => $fecha,
                        'tipo_cambio' => 1,
                        'campania' => $campania,
                        'costo_operativo' => $nombreCosto,
                        'costo_operativo_costo' => $valor
                    ];
                    break;
            }
        }

        return $informacion;
    }
    public function generarInformacionConsumo()
    {
        $informacion = [];


        $registros = CamposCampaniasConsumo::where('campos_campanias_id', $this->campoCampania->id)->get();
        if ($registros) {
            foreach ($registros as $registro) {
                if ($registro->reporte_file) {
                    $tipo = mb_strtolower(Str::slug($registro->categoriaProducto->nombre));

                    $hoja = ExcelHelper::cargarHoja('public', $registro->reporte_file, 'CONSUMOS');
                    $table = $hoja->getTableByName('ConsumosTable');

                    if (!$table) {
                        throw new Exception("No se encontró la tabla ConsumosTable.");
                    }

                    $tableRange = $table->getRange();
                    $data = $hoja->rangeToArray($tableRange, null, true, false, true);
                    if (!$data || count($data) < 2) {
                        continue;
                    }

                    $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

                    $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values();
                    $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values()->toArray();

                    // Mostrar datos reestructurados           
                    foreach ($data as $fila) {

                        switch ($tipo) {
                            case 'fertilizante':
                                $informacion[] = [
                                    'fecha' => $fila['fecha'],
                                    'tipo_cambio' => 1,
                                    'campania' => $fila['campana'],
                                    'horas' => '',
                                    'planilla_nombre' => '',
                                    'planilla_h' => '',
                                    'planilla_m' => '',
                                    'cuadrilla_fija_cantidad' => '',
                                    'cuadrilla_fija_costo' => '',
                                    'cuadrilla_cantidad' => '',
                                    'cuadrilla_costo' => '',
                                    'mano_obra' => '',
                                    'cantidad_jornales' => '',
                                    'costo' => '',
                                    'maquinaria' => '',
                                    'maquinaria_costo' => '',
                                    'consumo_fertilizante_cantidad' => $fila['cantidad'],
                                    'consumo_fertilizante_nombre_comercial' => $fila['producto'],
                                    'consumo_fertilizante_orden_compra' => $fila['orden_de_compra'],
                                    'consumo_fertilizante_tienda_comercial' => $fila['tienda_comercial'],
                                    'consumo_fertilizante_factura' => $fila['factura'],
                                    'consumo_fertilizante_costo' => $fila['total_costo'],
                                    'consumo_pesticida_cantidad' => '',
                                    'consumo_pesticida_nombre_comercial' => '',
                                    'consumo_pesticida_orden_compra' => '',
                                    'consumo_pesticida_tienda_comercial' => '',
                                    'consumo_pesticida_factura' => '',
                                    'consumo_pesticida_costo' => '',
                                ];
                                break;
                            case 'pesticida':
                                $informacion[] = [
                                    'fecha' => $fila['fecha'],
                                    'tipo_cambio' => 1,
                                    'campania' => $fila['campana'],
                                    'horas' => '',
                                    'planilla_nombre' => '',
                                    'planilla_h' => '',
                                    'planilla_m' => '',
                                    'cuadrilla_fija_cantidad' => '',
                                    'cuadrilla_fija_costo' => '',
                                    'cuadrilla_cantidad' => '',
                                    'cuadrilla_costo' => '',
                                    'mano_obra' => '',
                                    'cantidad_jornales' => '',
                                    'costo' => '',
                                    'maquinaria' => '',
                                    'maquinaria_costo' => '',
                                    'consumo_fertilizante_cantidad' => '',
                                    'consumo_fertilizante_nombre_comercial' => '',
                                    'consumo_fertilizante_orden_compra' => '',
                                    'consumo_fertilizante_tienda_comercial' => '',
                                    'consumo_fertilizante_factura' => '',
                                    'consumo_fertilizante_costo' => '',
                                    'consumo_pesticida_cantidad' => $fila['cantidad'],
                                    'consumo_pesticida_nombre_comercial' => $fila['producto'],
                                    'consumo_pesticida_orden_compra' => $fila['orden_de_compra'],
                                    'consumo_pesticida_tienda_comercial' => $fila['tienda_comercial'],
                                    'consumo_pesticida_factura' => $fila['factura'],
                                    'consumo_pesticida_costo' => $fila['total_costo'],
                                ];
                                break;
                            default:
                                # code...
                                break;
                        }

                    }
                }
            }
        }
        return $informacion;

    }
    public function generarInformacionCuadrilla()
    {
        $informacion = [];

        if ($this->campoCampania->gasto_cuadrilla_file) {

            $hoja = ExcelHelper::cargarHoja('public', $this->campoCampania->gasto_cuadrilla_file, 'GASTO CUADRILLA');

            $table = $hoja->getTableByName('GASTO_CUADRILLA');

            if (!$table) {
                throw new Exception("No se encontró la tabla GASTO_CUADRILLA.");
            }

            $tableRange = $table->getRange();
            $data = $hoja->rangeToArray($tableRange, null, true, false, true);

            if (!$data || count($data) < 2) {
                throw new Exception("No hay datos suficientes en la tabla.");

            }
            $headers = array_map(fn($header) => Str::slug($header, '_'), array_shift($data));

            $data = collect($data)->map(fn($row) => array_combine($headers, $row))->values();

            $agrupado = $data->groupBy(function ($fila) {
                return $fila['fecha'] . '-' . $fila['labor'] . '-' . $fila['costo_hora'] . '-' . $fila['total_de_horas'];
            });

            foreach ($agrupado as $clave => $grupo) {
                $filaEjemplo = $grupo->first();
                $cantidadCuadrilla = $grupo->count();
                $cantidadJornales = $grupo->sum(fn($fila) => CalculoHelper::calcularJornales($fila['total_de_horas']));
                $gastoTotal = $grupo->sum('gasto_total');
                $bonoTotal = $grupo->sum('gasto_bono');

                // Registro principal (mano de obra)
                $informacion[] = [
                    'fecha' => $filaEjemplo['fecha'],
                    'tipo_cambio' => 1,
                    'campania' => $filaEjemplo['campana'],
                    'horas' => $filaEjemplo['total_de_horas'],
                    'planilla_nombre' => '',
                    'planilla_h' => '',
                    'planilla_m' => '',
                    'cuadrilla_fija_cantidad' => '',
                    'cuadrilla_fija_costo' => '',
                    'cuadrilla_cantidad' => $cantidadCuadrilla,
                    'cuadrilla_costo' => $filaEjemplo['costo_hora'],
                    'mano_obra' => $filaEjemplo['labor'],
                    'cantidad_jornales' => $cantidadJornales,
                    'costo' => $gastoTotal,
                ];

                // Registro de bono (si hay)
                if ($bonoTotal > 0) {
                    $informacion[] = [
                        'fecha' => $filaEjemplo['fecha'],
                        'tipo_cambio' => 1,
                        'campania' => $filaEjemplo['campana'],
                        'horas' => $filaEjemplo['total_de_horas'],
                        'planilla_nombre' => '',
                        'planilla_h' => '',
                        'planilla_m' => '',
                        'cuadrilla_fija_cantidad' => '',
                        'cuadrilla_fija_costo' => '',
                        'cuadrilla_cantidad' => $cantidadCuadrilla,
                        'cuadrilla_costo' => $filaEjemplo['costo_hora'],
                        'mano_obra' => 'Bono por ' . $filaEjemplo['labor'],
                        'cantidad_jornales' => '',
                        'costo' => $bonoTotal,
                    ];
                }
            }
        }

        return $informacion;
    }
    public function actualizarConsumo()
    {
        $this->campoCampania->resumenConsumoProductos()->delete();
        $this->campoCampania->camposCampaniasConsumo()->delete();
        $fecha_inicio = $this->campoCampania->fecha_inicio;
        $fecha_fin = $this->campoCampania->fecha_fin;
        $campo = $this->campoCampania->campo;

        $query = AlmacenProductoSalida::whereDate('fecha_reporte', '>=', $fecha_inicio);
        if ($fecha_fin) {
            $query->whereDate('fecha_reporte', '<=', $fecha_fin);
        }
        $registros = $query->where('campo_nombre', $campo)->get();
        if ($registros) {

            $resumenConsumoProductosData = [];
            foreach ($registros as $registro) {

                $orden_compra = null;
                $tienda_comercial = null;
                $factura = null;
                if ($registro->compraSalida->count() > 0) {
                    $compraVinculaa = $registro->compraSalida->first();

                    $tienda_comercial = $compraVinculaa->tiendaComercial ? $compraVinculaa->tiendaComercial->nombre : '-';
                    $factura = $compraVinculaa->serie . '-' . $compraVinculaa->numero;
                }


                //solo se aceptan valores blanco, negro y -
                $tipoKardex = $registro->tipo_kardex ?? '-';


                $resumenConsumoProductosData[] = [
                    'fecha' => $registro->fecha_reporte,
                    'campo' => $registro->campo_nombre,
                    'producto' => $registro->producto->nombre_completo,
                    'categoria' => $registro->producto->categoria,
                    'cantidad' => $registro->cantidad,
                    'total_costo' => $registro->total_costo,
                    'campos_campanias_id' => $this->campoCampania->id,
                    'tipo_kardex' => $tipoKardex,

                    'orden_compra' => $orden_compra,
                    'tienda_comercial' => $tienda_comercial,
                    'factura' => $factura,
                ];
            }

            ResumenConsumoProductos::insert($resumenConsumoProductosData);

            $categoriaProductos = CategoriaProducto::all();
            if ($categoriaProductos) {
                $camposCampaniasConsumo = [];
                foreach ($categoriaProductos as $categoriaProducto) {

                    $datosFiltrados = array_filter($resumenConsumoProductosData, function ($dato) use ($categoriaProducto) {
                        return $dato['categoria_id'] === $categoriaProducto->id;
                    });

                    $totalConsumido = array_sum(array_column($datosFiltrados, 'total_costo'));
         

                    $spreadsheet = ExcelHelper::cargarPlantilla('reporte_comsumo_productos.xlsx');
                    $hoja = $spreadsheet->getSheetByName('CONSUMOS');

                    if (!$hoja) {
                        throw new Exception("No se ha configurado un formato para el documento a exportar.");
                    }

                    $table = $hoja->getTableByName('ConsumosTable');

                    if (!$table) {
                        throw new Exception("La plantilla no tiene una tabla llamada ConsumosTable.");
                    }

                    $fila = ExcelHelper::primeraFila($table) + 1;

                    foreach ($datosFiltrados as $index => $dato) {

                        $hoja->setCellValue("A{$fila}", $index + 1); // Índice (empieza en 1)
                        $hoja->setCellValue("B{$fila}", mb_strtoupper($dato['tipo_kardex']));
                        $hoja->setCellValue("C{$fila}", $this->campoCampania->nombre_campania);
                        $hoja->setCellValue("D{$fila}", $dato['fecha']);
                        $hoja->setCellValue("E{$fila}", $dato['campo']);
                        $hoja->setCellValue("F{$fila}", mb_strtoupper($dato['producto']));
                        $hoja->setCellValue("G{$fila}", mb_strtoupper($dato['categoria']));

                        $hoja->setCellValue("H{$fila}", $dato['orden_compra']);
                        $hoja->setCellValue("I{$fila}", $dato['tienda_comercial']);
                        $hoja->setCellValue("J{$fila}", $dato['factura']);

                        $hoja->setCellValue("K{$fila}", $dato['cantidad']);
                        $hoja->setCellValue("L{$fila}", ($dato['cantidad'] != 0) ? "=M{$fila}/K{$fila}" : "0");
                        $hoja->setCellValue("M{$fila}", $dato['total_costo']);

                        $fila++; // Mover a la siguiente fila
                    }

                    $hoja->setCellValue("A{$fila}", 'TOTALES');
                    $hoja->setCellValue("K{$fila}", "=SUM(ConsumosTable[Cantidad])");
                    $hoja->setCellValue("L{$fila}", "=SUM(ConsumosTable[Costo Unitario])");
                    $hoja->setCellValue("M{$fila}", "=SUM(ConsumosTable[Total Costo])");

                    ExcelHelper::actualizarRangoTabla($table, $fila - 1);

                    $folderPath = 'consumo_reportes/' . date('Y-m');
                    $fileName = 'REPORTE_CONSUMO_' . Str::slug('REPORTE_CONSUMO_' . mb_strtoupper($this->campoCampania->nombre_campania) . '_' . mb_strtoupper($categoriaProducto->nombre) . '_' . $this->campoCampania->campo) . '.xlsx';
                    $filePath = $folderPath . '/' . $fileName;

                    // Crear carpeta si no existe
                    Storage::disk('public')->makeDirectory($folderPath);

                    // Guardar el archivo en storage/app/public/reporte/YYYY-MM/
                    $writer = new Xlsx($spreadsheet);
                    $writer->save(Storage::disk('public')->path($filePath));
                    //Excel::store(new CampoConsumoExport($data), $filePath, 'public');
                    $camposCampaniasConsumo[] = [
                        'campos_campanias_id' => $this->campoCampania->id,
                        'categoria_id' => $categoriaProducto->id,
                        'monto' => $totalConsumido,
                        'reporte_file' => $filePath
                    ];
                }
                CamposCampaniasConsumo::insert($camposCampaniasConsumo);
            }
        }
    }
    public function gastoPlanilla()
    {
        return PlanillaServicio::calcularGastoPlanilla($this->campoCampaniaId);
    }
    public function gastoCuadrilla()
    {
        return CuadrillaServicio::calcularGastoCuadrilla($this->campoCampaniaId);
    }
}
