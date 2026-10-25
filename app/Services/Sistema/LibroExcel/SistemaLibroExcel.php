<?php

namespace App\Services\Sistema\LibroExcel;

use App\Exceptions\ErrorControlado;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Escribe y lee un libro Excel del sistema (recursos, insumos, empleados): "Léeme", una hoja por catálogo, la matriz
 * opcional y "Listas". Las columnas se leen por su encabezado (se pueden reordenar); una hoja que falta no se importa.
 *
 * Marca de versión: al descargar, el archivo guarda (propiedad oculta del documento) la firma de los datos del
 * sistema de esas hojas. Al subir se compara con la firma actual: si alguien cambió algo desde la descarga, no se
 * guarda hasta bajar el archivo de nuevo (así nadie pisa cambios con datos viejos).
 */
class SistemaLibroExcel
{
    private const FILAS_LISTA = 2000;
    public const PROPIEDAD_LIBRO = 'tsh_libro';
    public const PROPIEDAD_FIRMA = 'tsh_firma';
    public const PROPIEDAD_DESCARGA = 'tsh_descargado';
    public const PROPIEDAD_HOJAS = 'tsh_hojas';
    public const PROPIEDAD_SISTEMA = 'tsh_sistema';

    /** Identifica esta instalación (cada dominio tiene su APP_KEY): un archivo de otro sistema no se compara por versión. */
    public static function idSistema(): string
    {
        return substr(sha1((string) config('app.key')), 0, 12);
    }

    private SistemaLibroExcelDefinicion $def;
    /** @var class-string<SistemaLibroExcelReglas> */
    private string $reglas;
    private ?array $soloHojas = null;

    // ------------------------------------------------------------------ datos del sistema

    /** @return array<string, array<int, array<string, ?string>>> hoja => filas normalizadas */
    public function filasNormalizadas(SistemaLibroExcelDefinicion $def): array
    {
        $reglas = $def->reglas();
        $r = [];
        foreach ($def->filasSistema() as $hoja => $filas) {
            $columnas = $reglas::HOJAS[$hoja][2];
            $r[$hoja] = array_map(function (array $f) use ($columnas, $reglas) {
                $n = [];
                foreach ($columnas as $clave => $c) {
                    $n[$clave] = $reglas::normalizar($f[$clave] ?? null, $c['tipo']);
                }
                return $n + (isset($f['n']) ? ['n' => (string) $f['n']] : []);
            }, $filas);
        }
        return $r;
    }

    /** Firma de los datos del sistema de esas hojas (sin columnas solo informativas). */
    public function firma(SistemaLibroExcelDefinicion $def, ?array $hojas = null, ?array $filas = null): string
    {
        $reglas = $def->reglas();
        $filas ??= $this->filasNormalizadas($def);
        $datos = [];
        foreach ($filas as $hoja => $f) {
            if ($hojas !== null && !in_array($hoja, $hojas, true)) {
                continue;
            }
            $lectura = array_keys(array_filter($reglas::HOJAS[$hoja][2], fn($c) => !empty($c['lectura'])));
            $datos[$hoja] = array_map(fn($fila) => array_diff_key($fila, array_flip($lectura)), $f);
        }
        if ($reglas::MATRIZ && ($hojas === null || in_array($reglas::MATRIZ, $hojas, true))) {
            $datos[$reglas::MATRIZ] = $def->matrizSistema();
        }
        return substr(md5(json_encode($datos)), 0, 16);
    }

    // ------------------------------------------------------------------ escritura

    public const PREFIJO_FIRMA_HOJA = 'tsh_firma_';
    public const HOJA_CONFIG = '_tsh';
    public const HOJA_INDICE = 'Léeme';
    /** Código VBA de la macro (botones Actualizar / Subir), sacado de resources/excel/TierraSanta.bas */
    public const MACRO_BIN = 'resources/excel/libros-vba.bin';

    public static function macroDisponible(): bool
    {
        return is_file(base_path(self::MACRO_BIN));
    }

    /**
     * @param string[]|null $hojas claves de HOJAS (y la matriz); null = todas
     * @param array|null $macro con macro (.xlsm): ['libro', 'url', 'clave', 'correo', 'vence'] → botones Actualizar / Subir
     */
    public function exportar(SistemaLibroExcelDefinicion $def, ?array $hojas = null, ?array $macro = null): Spreadsheet
    {
        $this->def = $def;
        $this->reglas = $reglas = $def->reglas();
        $this->soloHojas = $hojas;
        $this->conMacro = $macro !== null;
        $filas = $this->filasNormalizadas($def);

        $libro = new Spreadsheet();
        $props = $libro->getProperties();
        $props->setTitle($reglas::TITULO)->setCreator(config('app.name'))
            ->setCustomProperty(self::PROPIEDAD_LIBRO, $reglas::PREFIJO_ARCHIVO)
            ->setCustomProperty(self::PROPIEDAD_FIRMA, $this->firma($def, $hojas, $filas))
            ->setCustomProperty(self::PROPIEDAD_DESCARGA, now()->format('Y-m-d H:i:s'))
            ->setCustomProperty(self::PROPIEDAD_HOJAS, $hojas ? implode(',', $hojas) : '*')
            ->setCustomProperty(self::PROPIEDAD_SISTEMA, self::idSistema());
        // Firma por hoja: permite subir una sola hoja (botón "Subir hoja" de la macro)
        foreach ($reglas::claves() as $clave) {
            if ($this->incluye($clave)) {
                $props->setCustomProperty(self::PREFIJO_FIRMA_HOJA . $clave, $this->firma($def, [$clave], $filas));
            }
        }
        $this->leeme($libro->getActiveSheet()->setTitle(self::HOJA_INDICE));
        if ($macro) {
            $this->ayuda($libro->createSheet());
        }
        $listas = $libro->createSheet();
        $listas->setTitle('Listas');
        $rangos = $this->listas($listas);

        $matrizAgregada = false;
        foreach ($reglas::HOJAS as $clave => [$titulo, , $columnas]) {
            if ($reglas::MATRIZ && $clave === $def->matrizAntesDe() && $this->incluye($reglas::MATRIZ)) {
                $this->hojaMatriz($libro->createSheet());
                $matrizAgregada = true;
            }
            if (!$this->incluye($clave)) {
                continue;
            }
            $h = $libro->createSheet();
            $h->setTitle($titulo);
            $this->hoja($h, $columnas, $filas[$clave] ?? [], $rangos);
        }
        if ($reglas::MATRIZ && !$matrizAgregada && $this->incluye($reglas::MATRIZ)) {
            $this->hojaMatriz($libro->createSheet());
        }
        $libro->setIndexByName('Listas', $libro->getSheetCount() - 1);
        if ($macro) {
            $this->configuracionMacro($libro, $macro);
        }
        $libro->setActiveSheetIndex(0);
        return $libro;
    }

    private bool $conMacro = false;

    /**
     * Hoja muy oculta "_tsh" que lee la macro (dirección del sistema, clave, libro, hojas, índice, correo, vencimiento y
     * cada hoja con su clave) + el código VBA. En las hojas de datos la fila 1 es más alta: ahí van los botones.
     */
    private function configuracionMacro(Spreadsheet $libro, array $macro): void
    {
        $c = $libro->createSheet();
        $c->setTitle(self::HOJA_CONFIG);
        $c->fromArray([
            [$macro['url']], [$macro['clave']], [$macro['libro']], [$this->soloHojas ? implode(',', $this->soloHojas) : '*'],
            [self::HOJA_INDICE], [$macro['correo']], [$macro['vence']->format('Y-m-d H:i')],
            // A8: "carga" = carga inicial (la macro permite subir sin actualizar antes)
            [$macro['modo'] ?? ''],
        ], null, 'A1');
        $fila = 10;
        foreach ($this->reglas::claves() as $clave) {
            if ($this->incluye($clave)) {
                $c->setCellValue("A{$fila}", $this->reglas::titulos([$clave])[0]);
                $c->setCellValue("B{$fila}", $clave);
                $fila++;
            }
        }
        $c->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        $libro->setMacrosCode(file_get_contents(base_path(self::MACRO_BIN)));
    }

    /** Guía de la macro: habilitar macros, botones y qué significa cada error. */
    private function ayuda(Worksheet $h): void
    {
        $h->setTitle('Ayuda');
        $lineas = [
            ['AYUDA'],
            [''],
            ['Habilitar los botones (una vez por archivo)'],
            ['1. Guarda el archivo en tu PC. 2. Clic derecho sobre el archivo → Propiedades → marca "Desbloquear" → Aceptar.'],
            ['3. Ábrelo y, si aparece la barra amarilla, pulsa "Habilitar contenido". Sin esto Excel no permite los botones.'],
            [''],
            ['Cómo se usa'],
            ['• En "' . self::HOJA_INDICE . '": "Actualizar todo" trae los datos de hoy de todas las hojas. Recién entonces aparece "Subir cambios".'],
            ['• En cada hoja: "Actualizar hoja" y "Subir hoja" trabajan solo con esa hoja (más rápido).'],
            ['• Al subir, el sistema revisa el archivo y la hoja "Resultado" muestra qué es nuevo, qué cambia y qué tiene errores. Nada se guarda sin tu confirmación.'],
            ['• Los cambios quedan a tu nombre en la auditoría del sistema.'],
            [''],
            ['Seguridad'],
            ['• Este archivo trae tu correo y una clave de acceso personal (vence en ' . SistemaLibroExcelAcceso::DIAS_VIGENCIA . ' días). Es como tu contraseña: no lo compartas.'],
            ['• Descargar el Excel de nuevo anula la clave del archivo anterior: solo el último descargado funciona.'],
            [''],
            ['Errores'],
        ];
        foreach (self::ERRORES as $codigo => $explicacion) {
            $lineas[] = ["{$codigo}: {$explicacion}"];
        }
        $h->fromArray($lineas, null, 'A1');
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        foreach ([3, 7, 13, 17] as $f) {
            $h->getStyle("A{$f}")->getFont()->setBold(true);
        }
        $h->getColumnDimension('A')->setWidth(140);
    }

    /** Códigos de error de la API (los muestra la macro) y qué hacer. */
    public const ERRORES = [
        'SIN_CLAVE' => 'el archivo no trae clave de acceso. Descárgalo desde el sistema.',
        'CLAVE_INVALIDA' => 'la clave fue reemplazada (descargaste el Excel otra vez) o no existe. Usa el último archivo descargado.',
        'CLAVE_VENCIDA' => 'pasaron más de ' . SistemaLibroExcelAcceso::DIAS_VIGENCIA . ' días desde la descarga. Descárgalo de nuevo.',
        'CORREO_NO_COINCIDE' => 'se modificó la configuración del archivo. Descárgalo de nuevo.',
        'USUARIO_INACTIVO' => 'tu usuario está desactivado. Habla con el administrador.',
        'LIBRO_NO_AUTORIZADO' => 'la clave es de otro libro. Descarga este Excel desde el sistema.',
        'SIN_PERMISO' => 'tu usuario no tiene el permiso de este libro (el mensaje dice cuál). Pídelo al administrador.',
        'DESACTUALIZADO' => 'alguien cambió estos datos en el sistema después de tu última actualización. Usa "Actualizar", pasa tus cambios y sube de nuevo.',
        'ARCHIVO_INVALIDO' => 'el archivo no se pudo leer (hojas o columnas cambiadas). Descárgalo de nuevo.',
        'ARCHIVO_GRANDE' => 'el archivo pasa el tamaño permitido.',
        'REVISION_NO_ENCONTRADA' => 'pasó mucho tiempo entre revisar y guardar. Vuelve a subir.',
        'DEMASIADOS_INTENTOS' => 'muchas peticiones seguidas. Espera un minuto.',
        'ERROR_INTERNO' => 'error del sistema; quedó registrado con un ticket. Avisa al administrador con ese número.',
    ];

    private function incluye(string $clave): bool
    {
        return $this->soloHojas === null || in_array($clave, $this->soloHojas, true);
    }

    private function hoja(Worksheet $h, array $columnas, array $filas, array $rangos): void
    {
        $claves = array_keys($columnas);
        $this->encabezado($h, array_map(fn($c) => $c['t'] . (!empty($c['req']) ? ' *' : ''), array_values($columnas)));

        $fila = 2;
        foreach ($filas as $f) {
            foreach ($claves as $i => $clave) {
                $v = $f[$clave] ?? null;
                if ($v === null) {
                    continue;
                }
                $celda = Coordinate::stringFromColumnIndex($i + 1) . $fila;
                match ($columnas[$clave]['tipo']) {
                    'entero', 'decimal' => is_numeric($v) ? $h->setCellValue($celda, (float) $v) : $h->setCellValueExplicit($celda, $v, DataType::TYPE_STRING),
                    'fecha' => $h->setCellValue($celda, ExcelDate::PHPToExcel(new \DateTime($v))),
                    default => $h->setCellValueExplicit($celda, $v, DataType::TYPE_STRING),
                };
            }
            $fila++;
        }

        $hasta = max(self::FILAS_LISTA, $fila + 200);
        foreach ($claves as $i => $clave) {
            $c = $columnas[$clave];
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $rango = "{$letra}2:{$letra}{$hasta}";
            if ($c['tipo'] === 'fecha') {
                $h->getStyle($rango)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            } elseif ($c['tipo'] === 'texto') {
                $h->getStyle($rango)->getNumberFormat()->setFormatCode('@');
            }
            if (!empty($c['lectura'])) {
                $h->getStyle("{$letra}1:{$letra}" . max(2, $fila - 1))->getFont()->setItalic(true)->getColor()->setRGB('6B7280');
            }
            $formula = isset($c['op']) || $c['tipo'] === 'sino' ? ($rangos[$c['op'] ?? 'sino'] ?? null) : (isset($c['ref']) ? $this->rangoReferencia($c['ref']) : null);
            if ($formula) {
                $v = new DataValidation();
                $v->setType(DataValidation::TYPE_LIST)->setShowDropDown(true)->setAllowBlank(true)
                    ->setShowErrorMessage(false) // sugerencia: el importador valida
                    ->setFormula1($formula);
                $h->setDataValidation($rango, $v);
            }
            $ancho = max(mb_strlen($c['t']) + 3, min(45, (int) collect($filas)->max(fn($f) => mb_strlen((string) ($f[$clave] ?? ''))) + 2));
            $h->getColumnDimension($letra)->setWidth($ancho);
        }
        if ($this->conMacro) {
            $h->getRowDimension(1)->setRowHeight(30); // botones Actualizar hoja / Subir hoja
        }
        $h->freezePane('A2');
        $h->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex(count($claves)) . '1');
    }

    private function rangoReferencia(string $ref): ?string
    {
        [$hoja, $columna] = explode('.', $ref);
        if (!$this->incluye($hoja) || !isset($this->reglas::HOJAS[$hoja])) {
            return null; // la hoja referida no viene en este libro (el importador valida contra el sistema)
        }
        [$titulo, , $columnas] = $this->reglas::HOJAS[$hoja];
        $letra = Coordinate::stringFromColumnIndex(array_search($columna, array_keys($columnas), true) + 1);
        return "'{$titulo}'!\${$letra}\$2:\${$letra}\$" . self::FILAS_LISTA;
    }

    private function hojaMatriz(Worksheet $h): void
    {
        $h->setTitle($this->reglas::TITULO_MATRIZ);
        $m = $this->def->matrizSistema();
        $fijas = $m['fijas'];
        $columnas = array_keys($m['columnas']);
        $this->encabezado($h, array_merge($fijas, $columnas));
        $fila = 2;
        foreach ($m['filas'] as $valores) {
            foreach (array_values($valores) as $i => $v) {
                $h->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $fila, $v);
            }
            $claveFila = end($valores);
            foreach ($columnas as $i => $col) {
                if (in_array($claveFila, $m['columnas'][$col], true)) {
                    $h->setCellValue(Coordinate::stringFromColumnIndex(count($fijas) + $i + 1) . $fila, 'X');
                }
            }
            $fila++;
        }
        foreach ($fijas as $i => $t) {
            $h->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($i === count($fijas) - 1 ? 42 : 22);
        }
        foreach ($columnas as $i => $col) {
            $letra = Coordinate::stringFromColumnIndex(count($fijas) + $i + 1);
            $h->getColumnDimension($letra)->setWidth(max(12, mb_strlen($col) + 2));
            $h->getStyle("{$letra}2:{$letra}{$fila}")->getAlignment()->setHorizontal('center');
        }
        $h->freezePane(Coordinate::stringFromColumnIndex(count($fijas) + 1) . '2');
    }

    /** @return array<string, string> lista => rango */
    private function listas(Worksheet $h): array
    {
        $rangos = [];
        $col = 1;
        foreach (array_merge(['sino' => ['SI', 'NO']], $this->reglas::OPCIONES) as $clave => $valores) {
            $letra = Coordinate::stringFromColumnIndex($col);
            $h->setCellValue("{$letra}1", $clave);
            foreach (array_values($valores) as $i => $v) {
                $h->setCellValueExplicit($letra . ($i + 2), (string) $v, DataType::TYPE_STRING);
            }
            $rangos[$clave] = "Listas!\${$letra}\$2:\${$letra}\$" . max(2, count($valores) + 1);
            $h->getColumnDimension($letra)->setWidth(18);
            $col++;
        }
        $h->getStyle('1:1')->getFont()->setBold(true);
        return $rangos;
    }

    private function leeme(Worksheet $h): void
    {
        $reglas = $this->reglas;
        $lineas = [
            [mb_strtoupper($reglas::TITULO)],
            ['Descárgalo (botón del sistema o escritorio de archivos del inicio), corrígelo o complétalo y súbelo.'],
            ['Antes de guardar se muestra qué es nuevo, qué cambia y qué tiene errores. Las filas con errores no se guardan.'],
            [''],
            ['Reglas'],
            ['• Las columnas con * son obligatorias. Cada hoja se identifica por su primera columna (código, nombre o documento).'],
            ['• Nunca se borra nada: lo que está en el sistema y no en el Excel solo se informa. Para retirar algo, usa la columna Activo = NO.'],
            ['• Puedes cambiar el orden de las columnas. Las listas desplegables son sugerencias; el sistema valida al subir.'],
            ['• Versión: el archivo recuerda cuándo se descargó. Si alguien cambió estos datos en el sistema después, hay que descargarlo de nuevo antes de subirlo.'],
        ];
        foreach ($this->def->leeme() as $l) {
            $lineas[] = ['• ' . $l];
        }
        $lineas[] = [''];
        $filaHojas = count($lineas) + 1;
        $lineas[] = ['Hojas'];
        foreach ($reglas::HOJAS as $clave => [$titulo]) {
            if ($this->incluye($clave)) {
                $lineas[] = ['• ' . $titulo];
            }
        }
        if ($reglas::MATRIZ && $this->incluye($reglas::MATRIZ)) {
            $lineas[] = ['• ' . $reglas::TITULO_MATRIZ];
        }
        if ($this->soloHojas !== null) {
            $lineas[] = [''];
            $lineas[] = ['Este archivo trae solo estas hojas y al subirlo desde su página solo se actualizan ellas.'];
        }
        $lineas[] = [''];
        $lineas[] = ['Descargado el ' . now()->format('d/m/Y H:i') . '.'];
        // Con macro, las dos primeras filas quedan libres para los botones Actualizar todo / Subir cambios
        $d = $this->conMacro ? 3 : 0;
        if ($d) {
            $h->getRowDimension(1)->setRowHeight(18);
            $h->getRowDimension(2)->setRowHeight(18);
            $lineas[1] = ['Usa "Actualizar todo" para traer los datos de hoy; luego aparece "Subir cambios". Guía y errores: hoja "Ayuda".'];
        }
        $h->fromArray($lineas, null, 'A' . (1 + $d));
        $h->getStyle('A' . (1 + $d))->getFont()->setBold(true)->setSize(14);
        $h->getStyle('A' . (5 + $d))->getFont()->setBold(true);
        $h->getStyle('A' . ($filaHojas + $d))->getFont()->setBold(true);
        $h->getColumnDimension('A')->setWidth(130);
    }

    private function encabezado(Worksheet $h, array $titulos): void
    {
        $h->fromArray($titulos, null, 'A1');
        $rango = 'A1:' . Coordinate::stringFromColumnIndex(count($titulos)) . '1';
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    }

    // ------------------------------------------------------------------ lectura

    /**
     * @return array{hojas: array<string, array<int, array>>, matriz: ?array, avisos: string[], version: array}
     *         filas con 'fila' (número de fila del Excel) y valores normalizados por clave
     */
    public function leer(SistemaLibroExcelDefinicion $def, string $ruta, ?array $hojas = null): array
    {
        $this->def = $def;
        $this->reglas = $reglas = $def->reglas();
        $this->soloHojas = $hojas;
        $lector = IOFactory::createReader('Xlsx');
        $lector->setReadDataOnly(true);
        $libro = $lector->load($ruta);
        $props = $libro->getProperties();

        $r = ['hojas' => [], 'matriz' => null, 'avisos' => [], 'version' => [
            'libro' => $props->getCustomPropertyValue(self::PROPIEDAD_LIBRO),
            'firma' => $props->getCustomPropertyValue(self::PROPIEDAD_FIRMA),
            'descargado' => $props->getCustomPropertyValue(self::PROPIEDAD_DESCARGA),
            'hojas' => ($h = $props->getCustomPropertyValue(self::PROPIEDAD_HOJAS)) && $h !== '*' ? explode(',', $h) : null,
            'sistema' => $props->getCustomPropertyValue(self::PROPIEDAD_SISTEMA),
            'firmas' => collect($reglas::claves())->mapWithKeys(fn($c) => [$c => $props->getCustomPropertyValue(self::PREFIJO_FIRMA_HOJA . $c)])->filter()->all(),
        ]];
        if ($r['version']['libro'] && $r['version']['libro'] !== $reglas::PREFIJO_ARCHIVO) {
            throw new ErrorControlado("Este archivo es del Excel {$r['version']['libro']}, no de " . mb_strtolower($reglas::TITULO) . '.');
        }

        foreach ($reglas::HOJAS as $clave => [$titulo, , $columnas]) {
            $h = $this->incluye($clave) ? $libro->getSheetByName($titulo) : null;
            if (!$h) {
                continue;
            }
            $mapa = $this->mapaEncabezados($h, $columnas);
            if (!isset($mapa[array_key_first($columnas)])) {
                $r['avisos'][] = "La hoja \"{$titulo}\" no tiene la columna \"{$columnas[array_key_first($columnas)]['t']}\": no se lee.";
                continue;
            }
            $filas = [];
            $ultima = $h->getHighestDataRow();
            for ($n = 2; $n <= $ultima; $n++) {
                $f = ['fila' => $n];
                $vacia = true;
                foreach ($columnas as $c => $d) {
                    $v = isset($mapa[$c]) ? $h->getCell([$mapa[$c], $n])->getValue() : null;
                    $f[$c] = $reglas::normalizar($v, $d['tipo']);
                    $vacia = $vacia && $f[$c] === null;
                }
                if (!$vacia) {
                    $filas[] = $f;
                }
            }
            $r['hojas'][$clave] = $filas;
        }

        if ($reglas::MATRIZ && $this->incluye($reglas::MATRIZ) && ($h = $libro->getSheetByName($reglas::TITULO_MATRIZ))) {
            $fijas = count($def->matrizSistema()['fijas']);
            $ultimaCol = Coordinate::columnIndexFromString($h->getHighestDataColumn());
            $columnas = [];
            for ($c = $fijas + 1; $c <= $ultimaCol; $c++) {
                $nombre = trim((string) $h->getCell([$c, 1])->getValue());
                if ($nombre !== '') {
                    $columnas[$c] = $nombre;
                }
            }
            $marcas = array_fill_keys($columnas, []);
            for ($n = 2; $n <= $h->getHighestDataRow(); $n++) {
                $claveFila = trim((string) $h->getCell([$fijas, $n])->getValue());
                if ($claveFila === '') {
                    continue;
                }
                foreach ($columnas as $c => $col) {
                    if (trim((string) $h->getCell([$c, $n])->getValue()) !== '') {
                        $marcas[$col][] = $claveFila;
                    }
                }
            }
            $r['matriz'] = ['columnas' => array_values($columnas), 'marcas' => $marcas];
        }

        if (!$r['hojas'] && !$r['matriz']) {
            throw new ErrorControlado('El archivo no tiene ' . ($hojas === null ? 'ninguna hoja de ' . mb_strtolower($reglas::TITULO) : 'la hoja ' . implode(' / ', $reglas::titulos($hojas)))
                . '. Descarga primero el Excel, complétalo y súbelo con esa hoja.');
        }
        return $r;
    }

    /** @return array<string, int> clave de columna => índice de columna en la hoja */
    private function mapaEncabezados(Worksheet $h, array $columnas): array
    {
        $norm = fn($t) => mb_strtolower(trim(str_replace('*', '', (string) $t)));
        $porTitulo = [];
        foreach ($columnas as $clave => $c) {
            $porTitulo[$norm($c['t'])] = $clave;
        }
        // Archivos descargados antes de que el título ganara una aclaración: "Desde" vale por "Desde (día 1 del mes)"
        foreach ($columnas as $clave => $c) {
            $corto = $norm(preg_replace('/\s*\(.*\)\s*$/u', '', $c['t']));
            $porTitulo[$corto] ??= $clave;
        }
        $mapa = [];
        $ultima = Coordinate::columnIndexFromString($h->getHighestDataColumn());
        for ($i = 1; $i <= $ultima; $i++) {
            $clave = $porTitulo[$norm($h->getCell([$i, 1])->getValue())] ?? null;
            if ($clave && !isset($mapa[$clave])) {
                $mapa[$clave] = $i;
            }
        }
        return $mapa;
    }
}
