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
            // Lo verificado se revisó con la versión anterior: hay que verificar de nuevo
            $carga->detalles()->where('estado', KardexCargaDetalle::VERIFICADO)->update([
                'estado' => KardexCargaDetalle::PENDIENTE,
                'mensaje' => "Verificado con la versión anterior del macro: vuelve a verificar con la v{$version}.",
            ]);
            $this->sincronizarIndice($carga);
            return $carga;
        });
    }

    public const MODO_VERIFICAR = 'verificar';
    public const MODO_IMPORTAR_VERIFICADOS = 'importar_verificados';
    public const MODO_EJECUTAR_PENDIENTES = 'ejecutar_pendientes';
    public const MODO_EJECUTAR_TODOS = 'ejecutar_todos';

    /**
     * Verificación previa: producto, hoja y todas las observaciones de la hoja, SIN escribir nada (si el kardex no
     * existe se verifica con uno sin guardar). Sin errores queda "verificado". No lanza excepciones.
     */
    public function verificar(int $detalleId): KardexCargaDetalle
    {
        return $this->ejecutarFila($detalleId, false);
    }

    /** Importa (y antes verifica) el kardex de una fila, y lo regenera. No lanza excepciones. */
    public function procesar(int $detalleId): KardexCargaDetalle
    {
        return $this->ejecutarFila($detalleId, true);
    }

    /**
     * Filas para cada botón, en orden del índice:
     * - verificar / ejecutar_pendientes: las que aún no se importaron con éxito.
     * - importar_verificados: solo las verificadas sin observaciones.
     * - ejecutar_todos: todas con movimientos (incluye las importadas: se vuelven a importar).
     */
    public function idsPara(int $cargaId, string $modo): array
    {
        $estados = match ($modo) {
            self::MODO_IMPORTAR_VERIFICADOS => [KardexCargaDetalle::VERIFICADO],
            self::MODO_EJECUTAR_TODOS => [KardexCargaDetalle::PENDIENTE, KardexCargaDetalle::VERIFICADO, KardexCargaDetalle::ERROR,
                KardexCargaDetalle::SIN_PRODUCTO, KardexCargaDetalle::EXITO],
            default => [KardexCargaDetalle::PENDIENTE, KardexCargaDetalle::VERIFICADO, KardexCargaDetalle::ERROR,
                KardexCargaDetalle::SIN_PRODUCTO],
        };

        return KardexCargaDetalle::where('kardex_carga_id', $cargaId)
            ->whereIn('estado', $estados)
            ->orderBy('fila')
            ->pluck('id')
            ->all();
    }

    private function ejecutarFila(int $detalleId, bool $importar): KardexCargaDetalle
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

            // Al verificar no se crea nada: si el kardex no existe se usa uno sin guardar
            [$kardex, $creado] = $importar
                ? $this->kardexDe($producto, $carga, $detalle)
                : [$this->kardexExistente($producto, $carga) ?? $this->kardexSinGuardar($producto, $carga, $detalle), false];
            if ($kardex->exists) {
                $detalle->fill(['kardex_id' => $kardex->id, 'kardex_creado' => $creado]);
            }

            // Mismo importador del detalle del kardex; junta todas las observaciones de la hoja
            $datos = $this->importador->previsualizarDesdeRuta($ruta, $kardex, $hoja);
            $avisos = $this->textoAdvertencias($datos['advertencias'] ?? []);
            $nCompras = collect($datos['compras']['propuestas'])->sum(fn($g) => count($g['lineas']));
            $nSalidas = count($datos['salidas']['propuestas']);

            if (!$importar) {
                $detalle->fill(['compras' => $nCompras, 'salidas' => $nSalidas]);
                return $this->terminar($detalle, KardexCargaDetalle::VERIFICADO,
                    "Sin observaciones: {$nCompras} compra(s) y {$nSalidas} salida(s) listas para importar"
                    . ($kardex->exists ? '.' : ' (el kardex se creará al importar).') . $avisos);
            }

            $resultado = $this->importador->confirmarImportacion($datos, $kardex);
            $this->movimientos->generarMovimientos($kardex->fresh());

            $detalle->fill(['compras' => $resultado['comprasCreadas'], 'salidas' => $resultado['salidasCreadas']]);
            return $this->terminar($detalle, KardexCargaDetalle::EXITO,
                ($creado ? 'Kardex creado. ' : '') . "Importado y regenerado: {$resultado['comprasCreadas']} compra(s), {$resultado['salidasCreadas']} salida(s)." . $avisos);
        } catch (ValidationException $e) {
            return $this->terminar($detalle, KardexCargaDetalle::ERROR, implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            return $this->terminar($detalle, KardexCargaDetalle::ERROR, $e->getMessage());
        }
    }

    private function textoAdvertencias(array $advertencias): string
    {
        return $advertencias
            ? "\n" . count($advertencias) . " advertencia(s), no impiden importar:\n- " . implode("\n- ", array_slice($advertencias, 0, 100))
                . (count($advertencias) > 100 ? "\n- … y " . (count($advertencias) - 100) . ' más.' : '')
            : '';
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
    private function kardexExistente(Producto $producto, KardexCarga $carga): ?InsKardex
    {
        return InsKardex::where('producto_id', $producto->id)
            ->where('anio', $carga->anio)
            ->where('tipo', $carga->tipo_kardex)
            ->first();
    }

    /** Kardex en memoria para verificar sin crear nada (el importador solo lee producto, año, tipo y saldo). */
    private function kardexSinGuardar(Producto $producto, KardexCarga $carga, KardexCargaDetalle $detalle): InsKardex
    {
        $kardex = new InsKardex([
            'producto_id' => $producto->id,
            'codigo_existencia' => $detalle->codigo_existencia,
            'anio' => $carga->anio,
            'tipo' => $carga->tipo_kardex,
            'stock_inicial' => 0,
            'costo_total' => 0,
            'metodo_valuacion' => 'promedio',
        ]);
        $kardex->setRelation('producto', $producto);
        return $kardex;
    }

    private function kardexDe(Producto $producto, KardexCarga $carga, KardexCargaDetalle $detalle): array
    {
        $kardex = $this->kardexExistente($producto, $carga);
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
        // Puede traer todas las observaciones de la hoja (hasta 100 filas): el campo es TEXT
        $detalle->fill(['estado' => $estado, 'mensaje' => mb_strimwidth($mensaje, 0, 20000, '…')])->save();
        return $detalle;
    }
}
