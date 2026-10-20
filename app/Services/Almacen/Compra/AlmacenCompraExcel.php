<?php

namespace App\Services\Almacen\Compra;

use App\Models\Compra;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel de compras (mismo formato para exportar e importar):
 * - "Compras": una fila por producto con los datos del comprobante repetidos (las compras del mes elegido).
 * - "Productos": todos los productos del sistema ordenados por descripción; aquí se agregan los nuevos.
 * - "Proveedores": RUC y razón social; aquí se agregan los nuevos.
 * - "Listas": códigos válidos (comprobantes, almacenes, categorías, unidades, tipo de existencia).
 * Las columnas Producto, RUC, Tipo, Kardex, Almacén, Moneda y Forma de pago tienen lista desplegable (sugerencia: se
 * puede escribir otra cosa; el importador avisa).
 */
class AlmacenCompraExcel
{
    private const FILAS_EDITABLES = 3000;

    public function __construct(private AlmacenCompraCatalogoConsulta $catalogo)
    {
    }

    public function exportar(int $anio, int $mes): Spreadsheet
    {
        $libro = new Spreadsheet();
        $compras = $libro->getActiveSheet()->setTitle('Compras');
        $productos = $libro->createSheet()->setTitle('Productos');
        $proveedores = $libro->createSheet()->setTitle('Proveedores');
        $listas = $libro->createSheet()->setTitle('Listas');

        $this->hojaCompras($compras, $anio, $mes);
        $nProductos = $this->hojaProductos($productos);
        $nProveedores = $this->hojaProveedores($proveedores);
        $rangos = $this->hojaListas($listas);
        $this->validaciones($compras, $nProductos, $nProveedores, $rangos);

        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    private function hojaCompras(Worksheet $h, int $anio, int $mes): void
    {
        $this->encabezado($h, array_values(AlmacenCompraReglas::COLUMNAS));
        $compras = Compra::with(['proveedor.persona', 'almacen', 'detalles.producto'])
            ->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes)
            ->orderBy('fecha_emision')->orderBy('id')->get();
        $descripciones = collect($this->catalogo->productos())->pluck('descripcion', 'id');

        $fila = 2;
        foreach ($compras as $c) {
            foreach ($c->detalles as $d) {
                $h->fromArray([
                    $c->fecha_emision ? ExcelDate::PHPToExcel($c->fecha_emision) : null,
                    $c->tipo_comprobante_codigo, $c->serie, $c->numero,
                    $c->proveedor?->persona?->numero_documento, $c->tipo_kardex, $c->almacen?->nombre,
                    $c->moneda, (float) $c->tipo_cambio, $c->forma_pago,
                    $c->fecha_vencimiento ? ExcelDate::PHPToExcel($c->fecha_vencimiento) : null,
                    $descripciones[$d->producto_id] ?? $d->producto?->nombre_completo, $d->nombre_presentacion,
                    (float) $d->cantidad, (float) $d->costo_unitario, (float) $d->porcentaje_descuento, (float) $d->porcentaje_igv,
                    (float) $d->total_linea, $c->notas,
                ], null, "A{$fila}", true);
                $fila++;
            }
        }
        $h->getStyle('A2:A' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $h->getStyle('K2:K' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $h->getStyle('B2:D' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('@');
        $h->getStyle('E2:E' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('@');
        $h->getStyle('N2:R' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('#,##0.00####');
        foreach (['A' => 12, 'B' => 10, 'C' => 8, 'D' => 12, 'E' => 14, 'F' => 10, 'G' => 18, 'H' => 8, 'I' => 9, 'J' => 10, 'K' => 12,
            'L' => 45, 'M' => 16, 'N' => 10, 'O' => 14, 'P' => 8, 'Q' => 7, 'R' => 14, 'S' => 30] as $col => $w) {
            $h->getColumnDimension($col)->setWidth($w);
        }
        $h->freezePane('A2');
    }

    private function hojaProductos(Worksheet $h): int
    {
        $this->encabezado($h, AlmacenCompraReglas::COLUMNAS_PRODUCTOS);
        $fila = 2;
        foreach ($this->catalogo->productos() as $p) {
            $h->fromArray([$p['descripcion'], $p['nombre'], $p['ingrediente'], $p['categoria'], $p['unidad'], $p['existencia']], null, "A{$fila}", true);
            $fila++;
        }
        $h->setCellValue('H1', 'Para agregar un producto nuevo: escribe nombre comercial, ingrediente (opcional), categoría y unidad en una fila nueva; la descripción puede quedar vacía (se arma sola).');
        foreach (['A' => 50, 'B' => 35, 'C' => 30, 'D' => 14, 'E' => 14, 'F' => 14] as $col => $w) {
            $h->getColumnDimension($col)->setWidth($w);
        }
        $h->freezePane('A2');
        return $fila - 2;
    }

    private function hojaProveedores(Worksheet $h): int
    {
        $this->encabezado($h, AlmacenCompraReglas::COLUMNAS_PROVEEDORES);
        $fila = 2;
        foreach ($this->catalogo->proveedores() as $v) {
            $h->setCellValueExplicit("A{$fila}", $v['ruc'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $h->setCellValue("B{$fila}", $v['razon_social']);
            $fila++;
        }
        $h->getStyle('A2:A' . self::FILAS_EDITABLES)->getNumberFormat()->setFormatCode('@');
        $h->getColumnDimension('A')->setWidth(16);
        $h->getColumnDimension('B')->setWidth(50);
        $h->freezePane('A2');
        return $fila - 2;
    }

    /** @return array<string, string> lista => rango ($A$2:$A$n) */
    private function hojaListas(Worksheet $h): array
    {
        $bloques = [
            'tipos' => ['Tipo comprobante', $this->catalogo->tiposComprobante()],
            'almacenes' => ['Almacén', array_combine(array_values($this->catalogo->almacenes()), array_values($this->catalogo->almacenes()))],
            'categorias' => ['Categoría', $this->catalogo->categorias()],
            'unidades' => ['Unidad (SUNAT)', $this->catalogo->unidades()],
            'existencias' => ['Tipo existencia', $this->catalogo->tiposExistencia()],
        ];
        $rangos = [];
        $col = 1;
        foreach ($bloques as $clave => [$titulo, $valores]) {
            $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $letraDesc = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $h->setCellValue("{$letra}1", $titulo);
            $h->setCellValue("{$letraDesc}1", 'Descripción');
            $fila = 2;
            foreach ($valores as $codigo => $desc) {
                $h->setCellValueExplicit("{$letra}{$fila}", (string) $codigo, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $h->setCellValue("{$letraDesc}{$fila}", $desc);
                $fila++;
            }
            $rangos[$clave] = "Listas!\${$letra}\$2:\${$letra}\$" . max(2, $fila - 1);
            $h->getColumnDimension($letraDesc)->setWidth(30);
            $col += 3;
        }
        $h->getStyle('1:1')->getFont()->setBold(true);
        return $rangos;
    }

    private function validaciones(Worksheet $h, int $nProductos, int $nProveedores, array $rangos): void
    {
        $hasta = self::FILAS_EDITABLES;
        $lista = function (string $rango, string $formula) use ($h) {
            $v = new DataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setShowDropDown(true)->setAllowBlank(true)
                ->setShowErrorMessage(false) // sugerencia: se puede escribir otro valor (el importador avisa)
                ->setFormula1($formula);
            $h->setDataValidation($rango, $v);
        };
        $lista("L2:L{$hasta}", 'Productos!$A$2:$A$' . (self::FILAS_EDITABLES));
        $lista("E2:E{$hasta}", 'Proveedores!$A$2:$A$' . (self::FILAS_EDITABLES));
        $lista("B2:B{$hasta}", $rangos['tipos']);
        $lista("G2:G{$hasta}", $rangos['almacenes']);
        $lista("F2:F{$hasta}", '"blanco,negro"');
        $lista("H2:H{$hasta}", '"PEN,USD"');
        $lista("J2:J{$hasta}", '"contado,credito"');
    }

    private function encabezado(Worksheet $h, array $columnas): void
    {
        $h->fromArray($columnas, null, 'A1');
        $rango = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columnas)) . '1';
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    }

    // ------------------------------------------------------------------ lectura

    /**
     * @return array{compras: array<int, array>, productos: array<int, array>, proveedores: array<int, array>}
     *         filas con 'fila' (número de fila del Excel) y sus valores por clave
     */
    public function leer(string $ruta): array
    {
        $libro = IOFactory::load($ruta);
        $hoja = fn($nombre) => $libro->getSheetByName($nombre);
        if (!$hoja('Compras')) {
            throw new \App\Exceptions\ErrorControlado('El Excel no tiene la hoja "Compras". Descarga la plantilla del mes y llénala.');
        }

        $claves = array_keys(AlmacenCompraReglas::COLUMNAS);
        $compras = [];
        foreach ($hoja('Compras')->getRowIterator(2) as $row) {
            $valores = [];
            foreach ($claves as $i => $clave) {
                $celda = $hoja('Compras')->getCell([$i + 1, $row->getRowIndex()]);
                $valores[$clave] = in_array($clave, ['fecha', 'fecha_vencimiento'], true) ? $this->fecha($celda->getValue()) : $this->texto($celda->getCalculatedValue());
            }
            if (collect($valores)->filter(fn($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }
            $compras[] = ['fila' => $row->getRowIndex()] + $valores;
        }

        $productos = [];
        if ($hoja('Productos')) {
            foreach ($hoja('Productos')->getRowIterator(2) as $row) {
                $r = $row->getRowIndex();
                $v = fn($c) => $this->texto($hoja('Productos')->getCell([$c, $r])->getCalculatedValue());
                $nombre = $v(2);
                $descripcion = $v(1) ?: ($nombre ? AlmacenCompraReglas::descripcion($nombre, $v(3)) : null);
                if (!$descripcion) {
                    continue;
                }
                $productos[] = ['fila' => $r, 'descripcion' => $descripcion, 'nombre' => $nombre, 'ingrediente' => $v(3),
                    'categoria' => $v(4), 'unidad' => $v(5), 'existencia' => $v(6)];
            }
        }

        $proveedores = [];
        if ($hoja('Proveedores')) {
            foreach ($hoja('Proveedores')->getRowIterator(2) as $row) {
                $r = $row->getRowIndex();
                $ruc = $this->texto($hoja('Proveedores')->getCell([1, $r])->getCalculatedValue());
                if ($ruc) {
                    $proveedores[] = ['fila' => $r, 'ruc' => $ruc, 'razon_social' => $this->texto($hoja('Proveedores')->getCell([2, $r])->getCalculatedValue())];
                }
            }
        }

        return compact('compras', 'productos', 'proveedores');
    }

    private function texto($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v) && floor($v) == $v && abs($v) < 1e15) {
            $v = (string) (int) $v; // RUC / número escritos como número
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    /** Fecha de celda: serie de Excel o texto dd/mm/aaaa (también d-m-aaaa, aaaa-mm-dd). */
    private function fecha($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString();
        }
        $v = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            [$y, $mes, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/\-. ](\d{1,2})[\/\-. ](\d{2}|\d{4})$/', $v, $m)) {
            [$d, $mes, $y] = [(int) $m[1], (int) $m[2], (int) (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3])];
        } else {
            return 'invalida:' . $v;
        }
        // checkdate: el 31/02 no se convierte en 03/03
        return checkdate($mes, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mes, $d) : 'invalida:' . $v;
    }
}
