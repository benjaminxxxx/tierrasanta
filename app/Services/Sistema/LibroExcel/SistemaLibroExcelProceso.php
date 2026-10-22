<?php

namespace App\Services\Sistema\LibroExcel;

use App\Exceptions\ErrorControlado;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Guarda un libro Excel ya revisado: lo vuelve a leer y analizar y aplica, en una transacción, solo las filas nuevas
 * o con cambios sin errores, hoja por hoja en el orden de las reglas (lo referido antes que lo que lo usa).
 * Nunca borra. Solo guarda si el archivo está al día (marca de versión): si alguien cambió esos datos desde la
 * descarga, hay que descargarlo de nuevo.
 */
class SistemaLibroExcelProceso
{
    public function __construct(
        private SistemaLibroExcel $excel,
        private SistemaLibroExcelAnalisis $analisis,
    ) {
    }

    /**
     * @param string[]|null $hojas solo estas hojas (desde la página de un recurso); null = todas
     * @return array<string, int> hoja (título) => filas guardadas
     */
    public function importar(SistemaLibroExcelDefinicion $def, string $ruta, ?array $hojas = null): array
    {
        $reglas = $def->reglas();
        $analisis = $this->analisis->analizar($def, $this->excel->leer($def, $ruta, $hojas), $hojas);
        self::exigirVersion($analisis['version']);
        $r = [];

        DB::transaction(function () use ($def, $reglas, $analisis, &$r) {
            foreach (array_keys($reglas::HOJAS) as $hoja) {
                if ($analisis['matriz'] && $hoja === $def->matrizAntesDe()) {
                    $r[$reglas::TITULO_MATRIZ] = $def->guardarMatriz($analisis['matriz']);
                    $analisis['matriz'] = null;
                }
                if (!isset($analisis['hojas'][$hoja])) {
                    continue;
                }
                $a = $analisis['hojas'][$hoja];
                $n = $def->guardarHoja($hoja, $a['filas']);
                if ($n === null) {
                    $filas = $def->ordenarParaGuardar($hoja, array_values(array_filter($a['filas'], fn($f) => in_array($f['estado'], ['nuevo', 'cambia'], true))));
                    foreach ($filas as $f) {
                        $def->guardar($hoja, $f['datos']);
                        AuditoriaServicio::registrar(
                            modelo: $reglas::TITULO . ': ' . $a['titulo'],
                            modeloId: mb_substr($f['clave'], 0, 64),
                            accion: $f['estado'] === 'nuevo' ? 'crear' : 'editar',
                            despues: $this->paraAuditoria($reglas, $hoja, $f['datos']),
                            observacion: 'Importación ' . $reglas::TITULO . ($f['cambios'] ? ': ' . mb_substr(implode('; ', $f['cambios']), 0, 200) : ''),
                        );
                    }
                    $n = count($filas);
                }
                $r[$a['titulo']] = $n;
            }
            if ($analisis['matriz']) {
                $r[$reglas::TITULO_MATRIZ] = $def->guardarMatriz($analisis['matriz']);
            }
        });
        $def->despuesDeGuardar();
        return array_filter($r);
    }

    /** Se puede guardar un archivo al día o uno descargado de otro sistema (sincronización entre dominios). */
    public static function permiteGuardar(array $version): bool
    {
        return in_array($version['estado'] ?? '', ['al_dia', 'otro_sistema'], true);
    }

    /** @throws ErrorControlado si el archivo no se descargó con los datos actuales */
    public static function exigirVersion(array $version): void
    {
        $cuando = $version['descargado'] ? Carbon::parse($version['descargado'])->format('d/m/Y H:i') : null;
        if ($version['estado'] === 'sin_marca') {
            throw new ErrorControlado('Este archivo no se descargó del sistema (o es una versión antigua). Descarga primero el Excel con los datos actuales, '
                . 'haz tus cambios en ese archivo y vuelve a subirlo.');
        }
        if ($version['estado'] === 'desactualizada') {
            throw new ErrorControlado("Los datos cambiaron en el sistema después de que descargaste este archivo ({$cuando}). Descárgalo de nuevo, "
                . 'pasa tus cambios a ese archivo nuevo y vuelve a subirlo: así no se pisan los cambios de otra persona.');
        }
    }

    /** Datos de la fila para la auditoría, sin columnas informativas ni secretas (claves). */
    private function paraAuditoria(string $reglas, string $hoja, array $datos): array
    {
        $ocultas = array_keys(array_filter($reglas::HOJAS[$hoja][2], fn($c) => !empty($c['lectura']) || !empty($c['secreta'])));
        return array_diff_key($datos, array_flip(array_merge($ocultas, ['fila'])));
    }
}
