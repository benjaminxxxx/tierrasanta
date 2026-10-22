<?php

namespace App\Services\Sistema\LibroExcel;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Archivo que descarga un usuario: con la macro (.xlsm, botones Actualizar / Subir) si está disponible, con la dirección
 * actual del sistema y una clave de acceso personal recién emitida (anula la anterior de ese libro). Sin macro, el .xlsx
 * de siempre (se sube por el sistema).
 */
class SistemaLibroExcelDescarga
{
    public function __construct(private SistemaLibroExcel $excel, private SistemaLibroExcelAcceso $acceso)
    {
    }

    /** @return array{0: Spreadsheet, 1: string} [libro, nombre del archivo] */
    public function preparar(User $usuario, string $libro, ?array $hojas = null): array
    {
        $def = SistemaLibroExcelRegistro::definicion($libro);
        $nombre = $def->reglas()::nombreArchivo($hojas);
        if (!SistemaLibroExcel::macroDisponible()) {
            return [$this->excel->exportar($def, $hojas), $nombre];
        }
        $clave = $this->acceso->emitir($usuario, $libro);
        $hoja = $this->excel->exportar($def, $hojas, [
            'libro' => $libro, 'url' => rtrim(url('/'), '/'), 'clave' => $clave['clave'], 'correo' => $usuario->email, 'vence' => $clave['vence'],
        ]);
        return [$hoja, preg_replace('/\.xlsx$/', '.xlsm', $nombre)];
    }
}
