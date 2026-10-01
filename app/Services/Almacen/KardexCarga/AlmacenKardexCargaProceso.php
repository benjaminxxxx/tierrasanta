<?php

namespace App\Services\Almacen\KardexCarga;

use App\Models\InsKardex;
use App\Models\KardexCarga;
use App\Models\KardexCargaDetalle;
use App\Models\Producto;
use App\Services\Almacen\Kardex\InsumoKardexImportarServicio;
use App\Services\Almacen\Kardex\InsumoKardexMovimientosServicio;
use App\Services\Almacen\Kardex\InsumoKardexServicio;
use App\Support\FormatoHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Carga de un macro de KARDEX anual, kardex por kardex (la pantalla llama a procesar() fila por fila).
 *
 * 1. subir(): guarda el archivo (para poder reprocesar ante fallos) y lee la hoja INDICE: una fila por kardex.
 *    Las filas sin totales de entradas/salidas quedan como "sin movimientos" y no se procesan.
 * 2. procesar(fila): busca el producto por nombre; busca (o crea) el kardex del año y tipo; importa SOLO la hoja de
 *    ese código con el mismo importador del detalle del kardex (sin previsualizar: confirma directo). El importador
 *    borra antes las compras, salidas y movimientos del kardex en el año. Luego regenera el kardex.
 * 3. reemplazarArchivo(): se corrige el macro y se sube otra vez; se conserva el estado de cada fila para
 *    reprocesar solo las que fallaron (o cualquiera).
 */
class AlmacenKardexCargaProceso
{
    private const DISCO = 'local';

    public function __construct(
        private AlmacenKardexCargaExcel $excel,
        private InsumoKardexImportarServicio $importador,
        private InsumoKardexMovimientosServicio $movimientos,
        private InsumoKardexServicio $kardexServicio,
    ) {
    }

    public function subir(UploadedFile $archivo, int $anio, string $tipo): KardexCarga
    {
        if (!in_array($tipo, ['blanco', 'negro'], true)) {
            throw ValidationException::withMessages(['tipo' => 'El tipo de kardex debe ser blanco o negro.']);
        }

        return DB::transaction(function () use ($archivo, $anio, $tipo) {
            $carga = KardexCarga::create([
                'archivo' => '',
                'nombre_original' => $archivo->getClientOriginalName(),
                'anio' => $anio,
                'tipo_kardex' => $tipo,
                'version_archivo' => 1,
                'subido_por' => Auth::id(),
                'subido_por_nombre' => Auth::user()?->name,
            ]);
            $ruta = $this->guardarArchivo($carga, $archivo, 1);
            $carga->update(['archivo' => $ruta]);

            $this->sincronizarIndice($carga);
            return $carga;
        });
    }

    /**
     * Macro corregido: reemplaza el archivo (se guarda la versión anterior) y vuelve a leer el índice. Las filas que
     * ya existían conservan su estado; las nuevas quedan pendientes.
     */
    public function reemplazarArchivo(int $cargaId, UploadedFile $archivo): KardexCarga
    {
        $carga = KardexCarga::findOrFail($cargaId);
        $version = $carga->version_archivo + 1;
        $ruta = $this->guardarArchivo($carga, $archivo, $version);

        return DB::transaction(function () use ($carga, $archivo, $ruta, $version) {
            $carga->update([
                'archivo' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'version_archivo' => $version,
            ]);
            $this->sincronizarIndice($carga);
            return $carga;
        });
    }

    /** Importa el kardex de una fila del índice. No lanza excepciones: el resultado queda en la fila. */
    public function procesar(int $detalleId): KardexCargaDetalle
    {
        @set_time_limit(600);
        $detalle = KardexCargaDetalle::with('carga')->findOrFail($detalleId);
        $carga = $detalle->carga;

        $detalle->fill([
            'intentos' => $detalle->intentos + 1,
            'version_archivo' => $carga->version_archivo,
            'procesado_at' => now(),
            'compras' => null,
            'salidas' => null,
        ]);

        if (!$detalle->tieneMovimientos()) {
            return $this->terminar($detalle, KardexCargaDetalle::SIN_MOVIMIENTOS, 'Sin totales de entradas ni salidas en el índice.');
        }

        try {
            $producto = $this->buscarProducto($detalle->nombre);
            if (!$producto) {
                return $this->terminar($detalle, KardexCargaDetalle::SIN_PRODUCTO,
                    "No hay un producto llamado \"{$detalle->nombre}\". Créalo (o corrige el nombre en el macro) y vuelve a procesar.");
            }
            $detalle->fill(['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre_comercial]);

            $ruta = Storage::disk(self::DISCO)->path($carga->archivo);
            $hoja = collect($this->excel->hojas($ruta))
                ->first(fn($h) => mb_strtoupper(trim($h)) === $detalle->codigo_existencia);
            if (!$hoja) {
                throw new RuntimeException("El macro no tiene la hoja \"{$detalle->codigo_existencia}\".");
            }

            [$kardex, $creado] = $this->kardexDe($producto, $carga, $detalle);
            $detalle->fill(['kardex_id' => $kardex->id, 'kardex_creado' => $creado]);

            // Mismo importador del detalle del kardex, sin previsualización: se confirma directo
            $datos = $this->importador->previsualizarDesdeRuta($ruta, $kardex, $hoja);
            $resultado = $this->importador->confirmarImportacion($datos, $kardex);
            $this->movimientos->generarMovimientos($kardex->fresh());

            $detalle->fill(['compras' => $resultado['comprasCreadas'], 'salidas' => $resultado['salidasCreadas']]);
            return $this->terminar($detalle, KardexCargaDetalle::EXITO,
                ($creado ? 'Kardex creado. ' : '') . "Importado y regenerado: {$resultado['comprasCreadas']} compra(s), {$resultado['salidasCreadas']} salida(s).");
        } catch (ValidationException $e) {
            return $this->terminar($detalle, KardexCargaDetalle::ERROR, implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            return $this->terminar($detalle, KardexCargaDetalle::ERROR, $e->getMessage());
        }
    }

    /** Filas que faltan procesar o que se pueden reintentar, en orden del índice. */
    public function idsPorProcesar(int $cargaId): array
    {
        return KardexCargaDetalle::where('kardex_carga_id', $cargaId)
            ->whereIn('estado', [KardexCargaDetalle::PENDIENTE, KardexCargaDetalle::ERROR, KardexCargaDetalle::SIN_PRODUCTO])
            ->orderBy('fila')
            ->pluck('id')
            ->all();
    }

    private function sincronizarIndice(KardexCarga $carga): void
    {
        $filas = $this->excel->leerIndice(Storage::disk(self::DISCO)->path($carga->archivo));
        $existentes = $carga->detalles()->get()->keyBy('codigo_existencia');

        foreach ($filas as $fila) {
            $detalle = $existentes->get($fila['codigo_existencia']) ?? new KardexCargaDetalle([
                'kardex_carga_id' => $carga->id,
                'estado' => KardexCargaDetalle::PENDIENTE,
            ]);
            $detalle->fill($fila);
            // Fila que ahora sí tiene movimientos (o que antes no los tenía): vuelve a quedar pendiente
            if (!$detalle->tieneMovimientos()) {
                $detalle->estado = KardexCargaDetalle::SIN_MOVIMIENTOS;
                $detalle->mensaje = 'Sin totales de entradas ni salidas en el índice.';
            } elseif ($detalle->estado === KardexCargaDetalle::SIN_MOVIMIENTOS) {
                $detalle->estado = KardexCargaDetalle::PENDIENTE;
                $detalle->mensaje = null;
            }
            $detalle->save();
        }
    }

    private function guardarArchivo(KardexCarga $carga, UploadedFile $archivo, int $version): string
    {
        $extension = $archivo->getClientOriginalExtension() ?: 'xlsm';
        return $archivo->storeAs("kardex-cargas/{$carga->id}", "v{$version}.{$extension}", self::DISCO);
    }

    /** Producto por nombre (sin mayúsculas, tildes ni espacios dobles). */
    private function buscarProducto(string $nombre): ?Producto
    {
        $clave = FormatoHelper::normalizarNombre($nombre);
        $coincidencias = Producto::get(['id', 'nombre_comercial'])
            ->filter(fn($p) => FormatoHelper::normalizarNombre($p->nombre_comercial) === $clave);

        if ($coincidencias->count() > 1) {
            throw new RuntimeException("Hay {$coincidencias->count()} productos llamados \"{$nombre}\": deja uno solo.");
        }
        return $coincidencias->first();
    }

    /** @return array{0: InsKardex, 1: bool} kardex del año y tipo; si no existe, se crea con el código del macro. */
    private function kardexDe(Producto $producto, KardexCarga $carga, KardexCargaDetalle $detalle): array
    {
        $kardex = InsKardex::where('producto_id', $producto->id)
            ->where('anio', $carga->anio)
            ->where('tipo', $carga->tipo_kardex)
            ->first();
        if ($kardex) {
            return [$kardex, false];
        }

        // El saldo inicial lo pone el importador (fila 17 de la hoja)
        $kardex = $this->kardexServicio->guardarInsumoKardex([
            'producto_id' => $producto->id,
            'codigo_existencia' => $detalle->codigo_existencia,
            'anio' => $carga->anio,
            'tipo' => $carga->tipo_kardex,
            'stock_inicial' => 0,
            'costo_total' => 0,
            'metodo_valuacion' => 'promedio',
        ]);
        return [$kardex, true];
    }

    private function terminar(KardexCargaDetalle $detalle, string $estado, string $mensaje): KardexCargaDetalle
    {
        $detalle->fill(['estado' => $estado, 'mensaje' => mb_strimwidth($mensaje, 0, 2000, '…')])->save();
        return $detalle;
    }
}
