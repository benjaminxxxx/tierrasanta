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

    /**
     * @param SistemaLibroExcelDefinicion|null $def otra fuente de datos para el mismo libro (ej. el conversor de la
     *   estructura antigua); null = la del sistema
     * @param string|null $modo "carga" = carga inicial: se sube sin "Actualizar" y se reconoce como de otro sistema
     * @return array{0: Spreadsheet, 1: string} [libro, nombre del archivo]
     */
    public function preparar(User $usuario, string $libro, ?array $hojas = null, ?SistemaLibroExcelDefinicion $def = null, ?string $modo = null): array
    {
        $def ??= SistemaLibroExcelRegistro::definicion($libro);
        $nombre = $def->reglas()::nombreArchivo($hojas);
        if (!SistemaLibroExcel::macroDisponible()) {
            return [$this->marcarCarga($this->excel->exportar($def, $hojas), $modo), $nombre];
        }
        $clave = $this->acceso->emitir($usuario, $libro);
        $hoja = $this->excel->exportar($def, $hojas, [
            'libro' => $libro, 'url' => rtrim(url('/'), '/'), 'clave' => $clave['clave'], 'correo' => $usuario->email, 'vence' => $clave['vence'],
            'modo' => $modo,
        ]);
        return [$this->marcarCarga($hoja, $modo), preg_replace('/\.xlsx$/', '.xlsm', $nombre)];
    }

    /** Una carga inicial no viene de este sistema: se compara fila por fila (estado otro_sistema) en vez de por versión. */
    private function marcarCarga(Spreadsheet $libro, ?string $modo): Spreadsheet
    {
        if ($modo === 'carga') {
            $libro->getProperties()->setCustomProperty(SistemaLibroExcel::PROPIEDAD_SISTEMA, 'carga-inicial');
        }
        return $libro;
    }
}
