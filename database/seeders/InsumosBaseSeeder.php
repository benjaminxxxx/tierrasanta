<?php

namespace Database\Seeders;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\InsCategoria;
use App\Models\InsTipoExistencia;
use App\Models\InsUnidad;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InsumosBaseSeeder extends Seeder
{
    public function run(): void
    {
        // TIPOS DE EXISTENCIA SEGÚN SUNAT – TABLA 5
        $tipos = [
            ['codigo' => '01', 'descripcion' => 'MERCADERÍA'],
            ['codigo' => '02', 'descripcion' => 'PRODUCTO TERMINADO'],
            ['codigo' => '03', 'descripcion' => 'MATERIAS PRIMAS Y AUXILIARES - MATERIALES'],
            ['codigo' => '04', 'descripcion' => 'ENVASES Y EMBALAJES'],
            ['codigo' => '05', 'descripcion' => 'SUMINISTROS DIVERSOS'],
            ['codigo' => '99', 'descripcion' => 'OTROS (ESPECIFICAR)'],
        ];

        foreach ($tipos as $item) {
            InsTipoExistencia::updateOrCreate(
                ['codigo' => $item['codigo']],      // PK
                ['descripcion' => $item['descripcion']]
            );
        }
        $unidades = [
            ['codigo' => '01', 'descripcion' => 'KILOGRAMOS', 'alias' => 'KG'],
            ['codigo' => '02', 'descripcion' => 'LIBRAS', 'alias' => 'LB'],
            ['codigo' => '03', 'descripcion' => 'TONELADAS LARGAS', 'alias' => 'TNL'],
            ['codigo' => '04', 'descripcion' => 'TONELADAS MÉTRICAS', 'alias' => 'TNM'],
            ['codigo' => '05', 'descripcion' => 'TONELADAS CORTAS', 'alias' => 'TNC'],
            ['codigo' => '06', 'descripcion' => 'GRAMOS', 'alias' => 'GR'],
            ['codigo' => '07', 'descripcion' => 'UNIDADES', 'alias' => 'UND'],
            ['codigo' => '08', 'descripcion' => 'LITROS', 'alias' => 'LT'],
            ['codigo' => '09', 'descripcion' => 'GALONES', 'alias' => 'GL'],
            ['codigo' => '10', 'descripcion' => 'BARRILES', 'alias' => 'BL'],
            ['codigo' => '11', 'descripcion' => 'LATAS', 'alias' => 'LAT'],
            ['codigo' => '12', 'descripcion' => 'CAJAS', 'alias' => 'CAJ'],
            ['codigo' => '13', 'descripcion' => 'MILLARES', 'alias' => 'MIL'],
            ['codigo' => '14', 'descripcion' => 'METROS CÚBICOS', 'alias' => 'M3'],
            ['codigo' => '15', 'descripcion' => 'METROS', 'alias' => 'M'],
            ['codigo' => '99', 'descripcion' => 'OTROS (ESPECIFICAR)', 'alias' => 'OTR'],
        ];

        foreach ($unidades as $item) {
            InsUnidad::updateOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'descripcion' => $item['descripcion'],
                    'alias' => $item['alias'],
                ]
            );
        }

        $categorias = [
            ['codigo' => 'fertilizante', 'descripcion' => 'Fertilizante'],
            ['codigo' => 'pesticida', 'descripcion' => 'Pesticida'],
            ['codigo' => 'combustible', 'descripcion' => 'Combustible'],
            ['codigo' => 'corrector_suelo', 'descripcion' => 'Corrector de Suelo'], // Saltrad
            ['codigo' => 'otros', 'descripcion' => 'Otros'],
        ];

        foreach ($categorias as $item) {
            InsCategoria::updateOrCreate(
                ['codigo' => $item['codigo']],
                ['descripcion' => $item['descripcion']]
            );
        }
    }
}
