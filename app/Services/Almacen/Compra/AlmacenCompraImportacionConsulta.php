<?php

namespace App\Services\Almacen\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;

/**
 * Vista previa de la importación de compras (no escribe): agrupa las filas por comprobante y, para cada compra, dice si
 * es nueva, si ya está registrada (RUC + tipo + serie + número) o si tiene errores que la bloquean. También lista los
 * productos y proveedores nuevos que se crearán (desde las hojas Productos / Proveedores).
 */
class AlmacenCompraImportacionConsulta
{
    public function __construct(private AlmacenCompraCatalogoConsulta $catalogo)
    {
    }

    /**
     * @param array{compras: array, productos: array, proveedores: array} $datos lo que devuelve AlmacenCompraExcel::leer()
     */
    public function analizar(array $datos): array
    {
        $tipos = $this->catalogo->tiposComprobante();
        $almacenes = array_flip(array_map(fn($n) => AlmacenCompraReglas::normalizar($n), $this->catalogo->almacenes()));
        $almacenPrincipal = array_key_first($this->catalogo->almacenes());
        $categorias = $this->catalogo->categorias();
        $unidades = $this->catalogo->unidades();

        // Proveedores: del sistema por RUC; los nuevos, de la hoja Proveedores
        $proveedoresSistema = collect($this->catalogo->proveedores())->keyBy('ruc');
        $proveedoresHoja = collect($datos['proveedores'])->keyBy('ruc');
        $proveedoresNuevos = [];

        // Productos: del sistema por descripción; los nuevos, de la hoja Productos
        $indice = $this->catalogo->indiceProductos();
        $descripciones = collect($this->catalogo->productos())->pluck('descripcion', 'id');
        $productosNuevos = [];
        foreach ($datos['productos'] as $p) {
            $clave = AlmacenCompraReglas::normalizar($p['descripcion']);
            if (isset($indice[$clave]) || isset($productosNuevos[$clave])) {
                continue;
            }
            $errores = [];
            if (!$p['nombre']) {
                $errores[] = 'falta el nombre comercial';
            }
            if (!$p['categoria'] || !isset($categorias[$p['categoria']])) {
                $errores[] = 'categoría "' . ($p['categoria'] ?? '') . '" no válida (ver hoja Listas)';
            }
            if ($p['unidad'] && !isset($unidades[$p['unidad']])) {
                $errores[] = "unidad \"{$p['unidad']}\" no válida";
            }
            $productosNuevos[$clave] = $p + ['errores' => $errores, 'usado' => false];
        }

        // Compras agrupadas por comprobante
        $grupos = [];
        foreach ($datos['compras'] as $f) {
            $clave = AlmacenCompraReglas::clave($f['ruc'], $f['tipo'], $f['serie'], $f['numero']);
            $grupos[$clave][] = $f;
        }

        $existentes = $this->existentes(array_keys($grupos));
        $compras = [];
        foreach ($grupos as $clave => $filas) {
            $c = $filas[0];
            $errores = [];
            $avisos = [];

            if (!$c['numero']) {
                $errores[] = 'Falta el número del comprobante (fila ' . $c['fila'] . ').';
            }
            if (!$c['fecha'] || str_starts_with($c['fecha'], 'invalida:')) {
                $errores[] = 'Fecha inválida: "' . str_replace('invalida:', '', (string) $c['fecha']) . '" (dd/mm/aaaa).';
            }
            $tipo = $c['tipo'] ? str_pad(preg_replace('/\D.*/', '', $c['tipo']) ?: $c['tipo'], 2, '0', STR_PAD_LEFT) : null;
            if (!$tipo || !isset($tipos[$tipo])) {
                $errores[] = 'Tipo de comprobante "' . ($c['tipo'] ?? '') . '" no válido (ver hoja Listas).';
            }
            if ($aviso = AlmacenCompraReglas::avisoSerie($tipo, $c['serie'])) {
                $avisos[] = $aviso;
            }
            $kardex = mb_strtolower((string) $c['kardex']);
            if (!in_array($kardex, ['blanco', 'negro'], true)) {
                $errores[] = 'Kardex debe ser "blanco" o "negro".';
            }

            // Proveedor
            $proveedor = $proveedoresSistema->get($c['ruc']);
            $proveedorNuevo = false;
            if (!$c['ruc']) {
                $errores[] = 'Falta el RUC del proveedor.';
            } elseif (!$proveedor) {
                $deHoja = $proveedoresHoja->get($c['ruc']);
                if ($deHoja && $deHoja['razon_social']) {
                    $proveedorNuevo = true;
                    $proveedoresNuevos[$c['ruc']] = $deHoja;
                } else {
                    $errores[] = "El RUC {$c['ruc']} no está registrado ni en la hoja Proveedores (agrega su razón social ahí).";
                }
            }

            $almacenId = $c['almacen'] ? ($almacenes[AlmacenCompraReglas::normalizar($c['almacen'])] ?? null) : $almacenPrincipal;
            if (!$almacenId) {
                $errores[] = "Almacén \"{$c['almacen']}\" no existe.";
            }

            // Líneas
            $lineas = [];
            foreach ($filas as $f) {
                $linea = ['fila' => $f['fila'], 'producto' => $f['producto'], 'presentacion' => $f['presentacion'], 'errores' => []];
                $claveProducto = AlmacenCompraReglas::normalizar($f['producto']);
                if (!$f['producto']) {
                    $linea['errores'][] = 'falta el producto';
                } elseif (isset($indice[$claveProducto])) {
                    $linea['producto_id'] = $indice[$claveProducto];
                } elseif (isset($productosNuevos[$claveProducto])) {
                    $linea['producto_nuevo'] = $claveProducto;
                    $productosNuevos[$claveProducto]['usado'] = true;
                    if ($productosNuevos[$claveProducto]['errores']) {
                        $linea['errores'][] = 'producto nuevo con datos incompletos en la hoja Productos';
                    }
                } else {
                    $linea['errores'][] = 'producto no existe (¿mal escrito?)';
                    $linea['sugerencias'] = $this->parecidos($f['producto'], $descripciones->all());
                }

                $cantidad = $this->numero($f['cantidad']);
                $precio = $this->numero($f['precio']);
                $subtotal = $this->numero($f['subtotal']);
                $descuento = $this->numero($f['descuento']) ?? 0.0;
                $igv = $this->numero($f['igv']) ?? 18.0;
                if (!$cantidad || $cantidad <= 0) {
                    $linea['errores'][] = 'cantidad inválida';
                }
                if ((!$precio || $precio <= 0) && (!$subtotal || $subtotal <= 0)) {
                    $linea['errores'][] = 'falta el precio o el subtotal';
                }
                // Si solo viene el subtotal, el precio se deduce (como en el formulario)
                if ((!$precio || $precio <= 0) && $subtotal > 0 && $cantidad > 0) {
                    $precio = round($subtotal / ($cantidad * (1 - $descuento / 100)), 6);
                }
                $subtotal = $subtotal > 0 ? $subtotal : round(($cantidad ?? 0) * ($precio ?? 0) * (1 - $descuento / 100), 2);
                $linea += ['cantidad' => $cantidad, 'precio' => $precio, 'descuento' => $descuento, 'igv' => $igv, 'subtotal' => $subtotal];
                foreach ($linea['errores'] as $e) {
                    $errores[] = "Fila {$f['fila']}: {$e}.";
                }
                $lineas[] = $linea;
            }
            if (collect($filas)->pluck('fecha')->unique()->count() > 1) {
                $avisos[] = 'Las filas del comprobante tienen fechas distintas: se toma la primera.';
            }

            $existente = $existentes[$clave] ?? null;
            if ($existente) {
                $errores = []; // ya registrada: no se importa, sus errores no importan
            }
            $compras[] = [
                'clave' => $clave,
                'estado' => $existente ? 'existente' : ($errores ? 'error' : 'nueva'),
                'compra_existente_id' => $existente,
                'fecha' => $c['fecha'],
                'tipo' => $tipo,
                'tipo_texto' => $tipos[$tipo] ?? $c['tipo'],
                'serie' => $c['serie'],
                'numero' => $c['numero'],
                'ruc' => $c['ruc'],
                'proveedor' => $proveedor['razon_social'] ?? ($proveedoresHoja->get($c['ruc'])['razon_social'] ?? null),
                'proveedor_id' => $proveedor['id'] ?? null,
                'proveedor_nuevo' => $proveedorNuevo,
                'kardex' => $kardex,
                'almacen_id' => $almacenId,
                'moneda' => in_array(mb_strtoupper((string) $c['moneda']), ['PEN', 'USD'], true) ? mb_strtoupper($c['moneda']) : 'PEN',
                'tipo_cambio' => $this->numero($c['tipo_cambio']) ?: 1,
                'forma_pago' => in_array(mb_strtolower((string) $c['forma_pago']), ['contado', 'credito'], true) ? mb_strtolower($c['forma_pago']) : 'contado',
                'fecha_vencimiento' => $c['fecha_vencimiento'] && !str_starts_with($c['fecha_vencimiento'], 'invalida:') ? $c['fecha_vencimiento'] : null,
                'notas' => $c['notas'],
                'lineas' => $lineas,
                'total' => round(array_sum(array_column($lineas, 'subtotal')), 2),
                'errores' => $errores,
                'avisos' => $avisos,
            ];
        }

        $productosNuevos = array_values(array_filter($productosNuevos, fn($p) => $p['usado'] || !$p['errores']));
        return [
            'compras' => $compras,
            'productos_nuevos' => $productosNuevos,
            'proveedores_nuevos' => array_values($proveedoresNuevos),
            'resumen' => [
                'nuevas' => collect($compras)->where('estado', 'nueva')->count(),
                'existentes' => collect($compras)->where('estado', 'existente')->count(),
                'con_error' => collect($compras)->where('estado', 'error')->count(),
                'total_nuevas' => round(collect($compras)->where('estado', 'nueva')->sum('total'), 2),
            ],
        ];
    }

    /** @return array<string, int> clave => compra_id de las compras ya registradas (incluye eliminadas) */
    private function existentes(array $claves): array
    {
        if (!$claves) {
            return [];
        }
        // leftJoin: compras antiguas sin proveedor (RUC vacío) también se reconocen como ya registradas
        return Compra::withTrashed()->leftJoin('proveedores as v', 'v.id', '=', 'compras.proveedor_id')
            ->leftJoin('personas as p', 'p.id', '=', 'v.persona_id')
            ->get(['compras.id', 'p.numero_documento', 'compras.tipo_comprobante_codigo', 'compras.serie', 'compras.numero'])
            ->mapWithKeys(fn($c) => [AlmacenCompraReglas::clave($c->numero_documento, $c->tipo_comprobante_codigo, $c->serie, $c->numero) => $c->id])
            ->only($claves)->all();
    }

    /** Hasta 3 productos con descripción parecida (para ver si se escribió mal). */
    private function parecidos(string $texto, array $descripciones): array
    {
        $t = AlmacenCompraReglas::normalizar($texto);
        return collect($descripciones)->map(function ($d) use ($t) {
            similar_text($t, AlmacenCompraReglas::normalizar($d), $pct);
            return ['descripcion' => $d, 'pct' => $pct];
        })->sortByDesc('pct')->take(3)->filter(fn($x) => $x['pct'] >= 50)->pluck('descripcion')->values()->all();
    }

    private function numero($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = str_replace([' ', 'S/', 'S/.'], '', (string) $v);
        // 1.234,56 → 1234.56 ; 1,234.56 → 1234.56
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $v)) {
            $v = str_replace(['.', ','], ['', '.'], $v);
        } else {
            $v = str_replace(',', '', $v);
        }
        return is_numeric($v) ? (float) $v : null;
    }
}
