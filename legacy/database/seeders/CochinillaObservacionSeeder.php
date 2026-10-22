<?php

namespace Database\Seeders;

use App\Models\CochinillaObservacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CochinillaObservacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datos = [
            'Infestador – Cartón',
            'Infestador – Malla',
            'Infestadores',
            'Mama',
            'Mama – Venta',
            'Poda – Cosecha',
            'Poda – Cosecha Cartón',
            'Poda – Cosecha Malla',
            'Poda – Cosecha Tubo',
            'Poda – Mama',
            'Pre - Cosecha',
            'Pre - Cosecha Cartón',
            'Pre - Cosecha Tubo',
        ];
        //CochinillaObservacion::truncate();
        foreach ($datos as $descripcion) {
            $codigo = $this->generarCodigoSlug($descripcion);

            CochinillaObservacion::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'descripcion' => $descripcion,
                    'es_cosecha_mama' => str_contains(Str::lower($descripcion), 'mama'),
                ]
            );
        }
    }
    private function generarCodigoSlug(string $texto): string
    {
        $texto = strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n'
        ]);
        $texto = preg_replace('/[^a-z0-9\s]/', '', $texto); // quita signos raros
        $texto = preg_replace('/\s+/', '_', $texto); // reemplaza espacios por guion bajo
        return $texto;
    }
}
