<?php

namespace App\Http\Controllers\Sistema;

use App\Constants\Permisos;
use App\Http\Controllers\Controller;
use App\Services\Almacen\Compra\AlmacenCompraExcel;
use App\Services\Cochinilla\Excel\CochinillaExcelExportarConsulta;
use App\Services\Cochinilla\Excel\CochinillaExcelReglas;
use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelRegistro;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descargas del escritorio de archivos del inicio: siempre con los datos frescos del sistema (no hay archivos
 * guardados). Recuerda, por usuario, cuándo bajó cada libro y con qué firma, para avisar si los datos cambiaron.
 */
class SistemaArchivoController extends Controller
{
    public function libro(string $libro): StreamedResponse
    {
        $def = SistemaLibroExcelRegistro::definicion($libro);
        abort_unless(Gate::allows($def->permiso()), 403);
        Cache::put(self::claveDescarga($libro), ['cuando' => now()->toDateTimeString(), 'firma' => app(SistemaLibroExcel::class)->firma($def)], now()->addDays(30));
        [$archivo, $nombre] = app(\App\Services\Sistema\LibroExcel\SistemaLibroExcelDescarga::class)->preparar(auth()->user(), $libro);
        return $this->enviar($archivo, $nombre);
    }

    public function cochinilla(int $anio): StreamedResponse
    {
        abort_unless(Gate::allows(Permisos::COCHINILLA_INGRESO), 403);
        return $this->enviar(app(CochinillaExcelExportarConsulta::class)->libro($anio), CochinillaExcelReglas::nombreArchivo($anio));
    }

    public function compras(int $anio, int $mes): StreamedResponse
    {
        abort_unless(Gate::allows(Permisos::INSUMO_COMPRA), 403);
        return $this->enviar(app(AlmacenCompraExcel::class)->exportar($anio, $mes), sprintf('COMPRAS %04d-%02d.xlsx', $anio, $mes));
    }

    /** Clave de caché con la última descarga del usuario de ese libro. */
    public static function claveDescarga(string $libro, ?int $usuarioId = null): string
    {
        return 'archivos.descarga.' . ($usuarioId ?? auth()->id()) . '.' . $libro;
    }

    private function enviar(Spreadsheet $libro, string $nombre): StreamedResponse
    {
        return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $nombre, [
            'Content-Type' => $libro->hasMacros() ? 'application/vnd.ms-excel.sheet.macroEnabled.12' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store',
        ]);
    }
}
