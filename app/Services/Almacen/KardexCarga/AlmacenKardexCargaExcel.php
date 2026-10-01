<?php

namespace App\Services\Almacen\KardexCarga;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Lectura del macro de KARDEX anual: hoja INDICE (desde la fila 11) con una fila por kardex.
 *
 *   B = código de existencia (= nombre de la hoja con el detalle del kardex)
 *   C = nombre del insumo
 *   F = total entradas unidades · G = total entradas importe
 *   H = total salidas unidades  · I = total salidas importe
 *
 * Solo se carga la hoja INDICE y se usan los valores ya calculados en el archivo (las fórmulas apuntan a otras
 * hojas que no se cargan: recalcular daría error).
 */
class AlmacenKardexCargaExcel
{
    public const HOJA_INDICE = 'INDICE';
    private const FILA_INICIO = 11;

    /**
     * @return array<int, array{fila: int, codigo_existencia: string, nombre: string, entradas_cantidad: ?float,
     *                          entradas_importe: ?float, salidas_cantidad: ?float, salidas_importe: ?float}>
     */
    public function leerIndice(string $ruta): array
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $hojaIndice = collect($reader->listWorksheetNames($ruta))
            ->first(fn($nombre) => mb_strtoupper(trim($nombre)) === self::HOJA_INDICE);
        if (!$hojaIndice) {
            throw new RuntimeException('El archivo no tiene la hoja "' . self::HOJA_INDICE . '".');
        }

        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$hojaIndice]);
        $hoja = $reader->load($ruta)->getSheetByName($hojaIndice);

        $filas = [];
        $vaciasSeguidas = 0;
        for ($fila = self::FILA_INICIO; $fila <= $hoja->getHighestDataRow(); $fila++) {
            $codigo = trim((string) $this->valor($hoja->getCell("B{$fila}")));
            $nombre = trim((string) $this->valor($hoja->getCell("C{$fila}")));

            if ($codigo === '' && $nombre === '') {
                // El índice termina cuando hay varias filas vacías seguidas
                if (++$vaciasSeguidas >= 5) {
                    break;
                }
                continue;
            }
            $vaciasSeguidas = 0;
            if ($codigo === '' || $nombre === '') {
                continue;
            }

            $filas[] = [
                'fila' => $fila,
                'codigo_existencia' => mb_strtoupper($codigo),
                'nombre' => $nombre,
                'entradas_cantidad' => $this->numero($hoja->getCell("F{$fila}")),
                'entradas_importe' => $this->numero($hoja->getCell("G{$fila}")),
                'salidas_cantidad' => $this->numero($hoja->getCell("H{$fila}")),
                'salidas_importe' => $this->numero($hoja->getCell("I{$fila}")),
            ];
        }

        if (!$filas) {
            throw new RuntimeException('La hoja INDICE no tiene filas desde la fila ' . self::FILA_INICIO . ' (códigos en B, nombres en C).');
        }
        return $filas;
    }

    /** Hojas del archivo (para validar que exista la hoja de cada código). */
    public function hojas(string $ruta): array
    {
        return IOFactory::createReaderForFile($ruta)->listWorksheetNames($ruta);
    }

    private function valor(Cell $celda): mixed
    {
        return $celda->isFormula() ? $celda->getOldCalculatedValue() : $celda->getValue();
    }

    private function numero(Cell $celda): ?float
    {
        $valor = $this->valor($celda);
        if ($valor === null || $valor === '' || $valor === '-') {
            return null;
        }
        return is_numeric($valor) ? (float) $valor : null;
    }
}
