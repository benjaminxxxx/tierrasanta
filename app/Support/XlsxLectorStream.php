<?php

namespace App\Support;

use XMLReader;
use ZipArchive;

/**
 * Lector rápido de .xlsx (solo valores): recorre el XML de la hoja en streaming y devuelve, de cada celda, el valor que
 * Excel guardó (en celdas con fórmula, su último resultado), sin cargar estilos, formatos ni rangos.
 *
 * Para libros pesados donde PhpSpreadsheet tarda demasiado (p. ej. el Excel histórico de cochinilla, cuya hoja "ingreso"
 * declara formato en 16 141 columnas): ese libro se lee aquí en segundos.
 */
class XlsxLectorStream
{
    private ZipArchive $zip;
    private array $compartidos = [];
    private array $hojas = []; // nombre => ruta dentro del zip

    public function __construct(string $ruta)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($ruta) !== true) {
            throw new \App\Exceptions\ErrorControlado('No se pudo abrir el Excel (¿es un .xlsx válido?).');
        }
        $this->leerHojas();
        $this->leerCompartidos();
    }

    public function __destruct()
    {
        @$this->zip->close();
    }

    /** @return string[] */
    public function nombresHojas(): array
    {
        return array_keys($this->hojas);
    }

    /**
     * Filas con datos de una hoja: número de fila => [valores de las columnas 1..$columnas].
     * Números como float, textos como string, errores (#DIV/0!) como string.
     *
     * @return array<int, array>
     */
    public function filas(string $hoja, int $columnas, int $desde = 1): array
    {
        $ruta = $this->hojas[$hoja] ?? null;
        if (!$ruta) {
            return [];
        }
        $xml = new XMLReader();
        $xml->XML($this->zip->getFromName($ruta), null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $filas = [];
        $fila = null;
        $valores = [];
        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'row') {
                $fila = (int) $xml->getAttribute('r');
                $valores = array_fill(0, $columnas, null);
                if ($xml->isEmptyElement) {
                    $fila = null;
                }
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'row') {
                if ($fila !== null && $fila >= $desde && array_filter($valores, fn($v) => $v !== null && $v !== '')) {
                    $filas[$fila] = $valores;
                }
                $fila = null;
            } elseif ($fila !== null && $xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'c') {
                $col = $this->columna((string) $xml->getAttribute('r'));
                if ($col < 1 || $col > $columnas || $xml->isEmptyElement) {
                    continue; // columnas sobrantes (formato): se saltan sin leer su contenido
                }
                $tipo = $xml->getAttribute('t');
                $valor = $this->valorCelda($xml, $tipo);
                $valores[$col - 1] = $valor;
            }
        }
        $xml->close();
        return $filas;
    }

    private function valorCelda(XMLReader $xml, ?string $tipo)
    {
        $profundidad = $xml->depth;
        $texto = null;
        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'c' && $xml->depth === $profundidad) {
                break;
            }
            if ($xml->nodeType === XMLReader::ELEMENT && ($xml->localName === 'v' || $xml->localName === 't')) {
                $texto = ($texto ?? '') . $xml->readString();
            }
        }
        if ($texto === null) {
            return null;
        }
        return match ($tipo) {
            's' => $this->compartidos[(int) $texto] ?? null,
            'str', 'inlineStr', 'e' => $texto,
            'b' => $texto === '1',
            default => is_numeric($texto) ? (float) $texto : $texto,
        };
    }

    /** "AQ12" → 43 */
    private function columna(string $ref): int
    {
        $n = 0;
        $len = strlen($ref);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($ref[$i]);
            if ($c < 65 || $c > 90) {
                break;
            }
            $n = $n * 26 + ($c - 64);
        }
        return $n;
    }

    private function leerHojas(): void
    {
        $rels = [];
        $x = simplexml_load_string((string) $this->zip->getFromName('xl/_rels/workbook.xml.rels'));
        foreach ($x->Relationship as $r) {
            $destino = ltrim((string) $r['Target'], '/');
            $rels[(string) $r['Id']] = str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino;
        }
        $libro = simplexml_load_string((string) $this->zip->getFromName('xl/workbook.xml'));
        $libro->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($libro->xpath('//m:sheets/m:sheet') as $s) {
            $id = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $this->hojas[(string) $s['name']] = $rels[$id] ?? null;
        }
    }

    private function leerCompartidos(): void
    {
        $contenido = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($contenido === false) {
            return;
        }
        $xml = new XMLReader();
        $xml->XML($contenido, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $actual = null;
        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'rPh') {
                $xml->next(); // guía fonética (japonés): no es parte del texto
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'si') {
                $actual = '';
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 't' && $actual !== null) {
                $actual .= $xml->readString();
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'si') {
                $this->compartidos[] = $actual;
                $actual = null;
            }
        }
        $xml->close();
    }
}
