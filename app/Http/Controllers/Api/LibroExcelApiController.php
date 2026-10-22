<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ErrorControlado;
use App\Http\Controllers\Controller;
use App\Http\Middleware\LibroExcelApiAcceso;
use App\Services\Sistema\Error\SistemaErrorCrud;
use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelAnalisis;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelProceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * API de los libros Excel con macro (botones Actualizar / Subir). El acceso (correo + clave personal, permiso) lo
 * valida LibroExcelApiAcceso; todo lo que se guarda queda a nombre de ese usuario.
 *  GET  /api/libros/{libro}/excel?hojas=a,b     → Excel con los datos de hoy de esas hojas (la macro copia sus valores)
 *  POST /api/libros/{libro}/revisar?hojas=a,b   → cuerpo: el archivo; responde el resultado (nuevo / cambia / errores)
 *  POST /api/libros/{libro}/guardar/{id}        → guarda lo revisado (vale 30 minutos y solo para el mismo usuario)
 * Respuestas en texto. Errores: "ERROR:CODIGO" + mensaje (ver SistemaLibroExcel::ERRORES).
 */
class LibroExcelApiController extends Controller
{
    private const MAX_BYTES = 20 * 1024 * 1024;
    private const MINUTOS_REVISION = 30;

    public function excel(Request $request): Response
    {
        return $this->seguro(function () use ($request) {
            $def = $this->def($request);
            $libro = app(SistemaLibroExcel::class)->exportar($def, $this->hojas($request, $def));
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), 'datos.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store',
            ]);
        });
    }

    public function revisar(Request $request): Response
    {
        return $this->seguro(function () use ($request) {
            $def = $this->def($request);
            $contenido = $request->getContent();
            if (strlen($contenido) === 0) {
                return LibroExcelApiAcceso::error(422, 'ARCHIVO_INVALIDO', 'No llegó ningún archivo.');
            }
            if (strlen($contenido) > self::MAX_BYTES) {
                return LibroExcelApiAcceso::error(413, 'ARCHIVO_GRANDE', 'El archivo pasa los 20 MB permitidos.');
            }
            if (!str_starts_with($contenido, "PK")) {
                return LibroExcelApiAcceso::error(422, 'ARCHIVO_INVALIDO', 'Lo que llegó no es un archivo de Excel.');
            }
            $hojas = $this->hojas($request, $def);
            $id = (string) Str::uuid();
            $ruta = "importaciones/libros/api_{$id}.xlsm";
            Storage::disk('local')->put($ruta, $contenido);

            try {
                $analisis = app(SistemaLibroExcelAnalisis::class)
                    ->analizar($def, app(SistemaLibroExcel::class)->leer($def, Storage::disk('local')->path($ruta), $hojas), $hojas);
            } catch (ErrorControlado $e) {
                Storage::disk('local')->delete($ruta);
                return LibroExcelApiAcceso::error(422, 'ARCHIVO_INVALIDO', $e->getMessage());
            }
            if (!SistemaLibroExcelProceso::permiteGuardar($analisis['version'])) {
                Storage::disk('local')->delete($ruta);
                try {
                    SistemaLibroExcelProceso::exigirVersion($analisis['version']);
                } catch (ErrorControlado $e) {
                    return LibroExcelApiAcceso::error(409, 'DESACTUALIZADO', $e->getMessage() . ' Usa "Actualizar" en el Excel.');
                }
            }
            Cache::put("libros.api.{$id}", ['usuario' => auth()->id(), 'libro' => $request->route('libro'), 'ruta' => $ruta, 'hojas' => $hojas],
                now()->addMinutes(self::MINUTOS_REVISION));
            return $this->texto($this->informe($id, $analisis));
        });
    }

    public function guardar(Request $request, string $libro, string $id): Response
    {
        return $this->seguro(function () use ($request, $libro, $id) {
            $revision = Cache::get("libros.api.{$id}");
            if (!$revision || $revision['libro'] !== $libro || $revision['usuario'] !== auth()->id()) {
                return LibroExcelApiAcceso::error(404, 'REVISION_NO_ENCONTRADA', 'No se encontró la revisión (vence a los ' . self::MINUTOS_REVISION
                    . ' minutos y solo la puede guardar quien la subió). Vuelve a subir.');
            }
            $def = $this->def($request);
            try {
                $r = app(SistemaLibroExcelProceso::class)->importar($def, Storage::disk('local')->path($revision['ruta']), $revision['hojas']);
            } catch (ErrorControlado $e) {
                return LibroExcelApiAcceso::error(409, 'DESACTUALIZADO', $e->getMessage());
            } finally {
                Cache::forget("libros.api.{$id}");
            }
            Storage::disk('local')->delete($revision['ruta']);
            $detalle = $r ? collect($r)->map(fn($n, $hoja) => "• {$hoja}: {$n}")->implode("\n") : 'No había nada nuevo ni con cambios.';
            return $this->texto('Guardado en el sistema a nombre de ' . auth()->user()->email . ' (' . now()->format('d/m/Y H:i') . ").\n{$detalle}\n\n"
                . 'Usa "Actualizar" antes de seguir editando.');
        });
    }

    // ------------------------------------------------------------------ apoyo

    private function def(Request $request): SistemaLibroExcelDefinicion
    {
        return $request->attributes->get('libroDefinicion');
    }

    /** @return string[]|null hojas pedidas ("*" o vacío = todas); las que no existen se ignoran */
    private function hojas(Request $request, SistemaLibroExcelDefinicion $def): ?array
    {
        $texto = trim((string) $request->query('hojas', '*'));
        if ($texto === '' || $texto === '*') {
            return null;
        }
        $hojas = array_values(array_intersect(array_map('trim', explode(',', $texto)), $def->reglas()::claves()));
        return $hojas ?: null;
    }

    /** Informe para la macro: ID, si se puede guardar, resumen y detalle por hoja (lo vuelca en la hoja "Resultado"). */
    private function informe(string $id, array $a): string
    {
        $r = $a['resumen'];
        $aplicar = ($r['nuevo'] ?? 0) + ($r['cambia'] ?? 0);
        $resumen = ($r['nuevo'] ?? 0) . ' nuevo(s), ' . ($r['cambia'] ?? 0) . ' con cambios, ' . ($r['igual'] ?? 0) . ' sin cambios, ' . ($r['error'] ?? 0) . ' con errores.';
        $l = ["ID:{$id}", 'GUARDAR:' . ($aplicar > 0 ? '1' : '0'), "RESUMEN:{$resumen}", ''];
        if ($a['version']['estado'] === 'otro_sistema') {
            $l[] = 'Este archivo viene de otro sistema: se compara con los datos de este.';
        }
        foreach ($a['avisos'] as $aviso) {
            $l[] = "Aviso: {$aviso}";
        }
        foreach ($a['faltantes'] as $hoja => $valores) {
            $l[] = "Falta cargar antes {$hoja}: " . implode(', ', array_slice($valores, 0, 20));
        }
        foreach ($a['hojas'] as $h) {
            $l[] = '';
            $l[] = "== {$h['titulo']}: " . collect($h['resumen'])->map(fn($n, $e) => "{$n} {$e}")->implode(', ');
            foreach ($h['filas'] as $f) {
                if ($f['estado'] === 'igual') {
                    continue;
                }
                $detalle = implode(' | ', array_merge($f['errores'], $f['cambios'], $f['avisos']));
                $l[] = mb_strtoupper($f['estado']) . " fila {$f['fila']} ({$f['clave']})" . ($detalle ? ": {$detalle}" : '');
            }
        }
        if ($m = $a['matriz']) {
            $l[] = '';
            $l[] = "== {$m['titulo']}: " . collect($m['resumen'])->map(fn($n, $e) => "{$n} {$e}")->implode(', ');
            foreach ($m['filas'] as $f) {
                if ($f['estado'] !== 'igual') {
                    $l[] = mb_strtoupper($f['estado']) . " {$f['nombre']}: agrega " . count($f['agrega']) . ', quita ' . count($f['quita']);
                }
            }
        }
        return implode("\n", $l);
    }

    private function texto(string $texto): Response
    {
        return response($texto, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** Errores inesperados: ticket en errores del sistema y respuesta ERROR_INTERNO con su número. */
    private function seguro(callable $accion): Response
    {
        try {
            return $accion();
        } catch (ErrorControlado $e) {
            return LibroExcelApiAcceso::error(422, 'ARCHIVO_INVALIDO', $e->getMessage());
        } catch (\Throwable $e) {
            $ticket = SistemaErrorCrud::registrar($e, static::class);
            return LibroExcelApiAcceso::error(500, 'ERROR_INTERNO', 'Ocurrió un error en el sistema' . ($ticket ? " (ticket #{$ticket->id})" : '')
                . '. Ya quedó registrado; avisa al administrador.');
        }
    }
}
