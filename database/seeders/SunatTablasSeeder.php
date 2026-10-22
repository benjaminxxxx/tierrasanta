<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Tablas SUNAT que usan compras y kardex (catálogos oficiales, iguales para cualquier empresa). */
class SunatTablasSeeder extends Seeder
{
    public function run(): void
    {
        $this->cargar('sunat_tabla10_tipo_comprobantes_pago', ['codigo', 'descripcion'], [
            ['00', 'Otros'], ['01', 'Factura'], ['03', 'Boleta de Venta'],
        ]);
        $this->cargar('sunat_tabla5_tipo_existencias', ['codigo', 'descripcion'], [
            ['01', 'MERCADERÍA'], ['02', 'PRODUCTO TERMINADO'], ['03', 'MATERIAS PRIMAS Y AUXILIARES - MATERIALES'],
            ['04', 'ENVASES Y EMBALAJES'], ['05', 'SUMINISTROS DIVERSOS'], ['06', 'SUMINISTROS'], ['07', 'REPUESTOS'],
            ['08', 'EMBALAJES'], ['09', 'SUBPRODUCTOS'], ['10', 'DESECHOS Y DESPERDICIOS'], ['91', 'OTROS 1'], ['92', 'OTROS 2'],
            ['93', 'OTROS 3'], ['94', 'OTROS 4'], ['95', 'OTROS 5'], ['96', 'OTROS 6'], ['97', 'OTROS 7'], ['98', 'OTROS 8'],
            ['99', 'OTROS (ESPECIFICAR)'],
        ]);
        $this->cargar('sunat_tabla6_codigo_unidad_medida', ['codigo', 'descripcion', 'alias'], [
            ['01', 'KILOGRAMOS', 'KG'], ['02', 'LIBRAS', 'LB'], ['03', 'TONELADAS LARGAS', 'TNL'], ['04', 'TONELADAS MÉTRICAS', 'TNM'],
            ['05', 'TONELADAS CORTAS', 'TNC'], ['06', 'GRAMOS', 'GR'], ['07', 'UNIDADES', 'UND'], ['08', 'LITROS', 'LT'],
            ['09', 'GALONES', 'GL'], ['10', 'BARRILES', 'BL'], ['11', 'LATAS', 'LAT'], ['12', 'CAJAS', 'CAJ'], ['13', 'MILLARES', 'MIL'],
            ['14', 'METROS CÚBICOS', 'M3'], ['15', 'METROS', 'M'], ['99', 'OTROS (ESPECIFICAR)', 'OTR'],
        ]);
    }

    private function cargar(string $tabla, array $columnas, array $filas): void
    {
        foreach ($filas as $f) {
            $fila = array_combine($columnas, $f);
            DB::table($tabla)->updateOrInsert(['codigo' => $fila['codigo']], $fila);
        }
    }
}
