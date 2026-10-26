<?php

// Métodos retirados de App\Services\Almacen\AlmacenServicio (ver legacy/README.md).
// Usaban helpers protegidos de AlmacenServicio (inicializarAcumulado, acumularValores, formatearRango).

namespace App\Services\Almacen;

class AlmacenServicioPesticidas
{
    public static function generarPesticidasXCampania($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            throw new Exception("La campaña no existe");
        }

        if (!$campania->area) {
            throw new Exception("Debe editar la campaña y modificar el área, debe ser diferente de vacío");
        }

        // Eliminar registros anteriores para evitar duplicados
        PesticidaCampania::where('campo_campania_id', $campania->id)->delete();

        // Obtener salidas de productos relacionadas al campo
        $salidas = AlmacenProductoSalida::where('campo_nombre', $campania->campo)
            ->whereHas('producto', function ($query) {
                $query->where('categoria', 'pesticida');
            })
            ->with('producto')
            ->get();


        $data = [];

        foreach ($salidas as $salida) {
            $kg_ha = ((float) $campania->area > 0) ? $salida->cantidad / (float) $campania->area : 0;

            $data[] = array_merge([
                'campo_campania_id' => $campania->id,
                'producto_id' => $salida->producto_id,
                'fecha' => $salida->fecha_reporte,
                'kg' => $salida->cantidad,
                'kg_ha' => $kg_ha,
            ]);
        }

        PesticidaCampania::insert($data);
    }

    public static function generarResumenPesticidaPorPeriodo($campaniaId)
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $pesticidas = PesticidaCampania::where('campo_campania_id', $campaniaId)
            ->with('producto.categoria')
            ->get();

        $resumen = [];
        $camposSumables = ['kg', 'kg_ha'];

        // Agrupamos primero por categoría
        $agrupadoPorCategoria = $pesticidas->groupBy(function ($f) {
            return $f->producto?->categoria?->descripcion ?? 'Sin categoría';
        });

        foreach ($agrupadoPorCategoria as $categoria => $grupoPorCategoria) {
            // Luego dentro de cada categoría, agrupamos por nombre del producto
            $agrupadoPorProducto = $grupoPorCategoria->groupBy(fn($f) => $f->producto->nombre_comercial ?? 'Producto desconocido');

            foreach ($agrupadoPorProducto as $producto => $registros) {
                $registrosOrdenados = $registros->sortBy('fecha')->values();
                $resumen[$categoria][$producto] = [];

                $grupoInicio = null;
                $grupoFin = null;
                $grupoAcumulado = [];

                foreach ($registrosOrdenados as $i => $registro) {
                    $fecha = Carbon::parse($registro->fecha);

                    if (is_null($grupoInicio)) {
                        $grupoInicio = $fecha->copy();
                        $grupoFin = $fecha->copy();
                        $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                    } else {
                        $prevFecha = $grupoFin->copy();
                        if ($fecha->isSameDay($prevFecha->addDay())) {
                            $grupoFin = $fecha->copy();
                            $grupoAcumulado = self::acumularValores($grupoAcumulado, $camposSumables, $registro);
                        } else {
                            $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                            $resumen[$categoria][$producto][$rango] = $grupoAcumulado;

                            // Reiniciar grupo
                            $grupoInicio = $fecha->copy();
                            $grupoFin = $fecha->copy();
                            $grupoAcumulado = self::inicializarAcumulado($camposSumables, $registro);
                        }
                    }

                    if ($i === $registrosOrdenados->count() - 1) {
                        $rango = self::formatearRango($grupoInicio, $grupoFin, $meses);
                        $resumen[$categoria][$producto][$rango] = $grupoAcumulado;
                    }
                }
            }
        }

        return $resumen;
    }
}
