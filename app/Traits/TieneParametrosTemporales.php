<?php
namespace App\Traits;

use App\Models\ParametroTemporal;

trait TieneParametrosTemporales
{
    // Define en el componente qué parámetros manejar:
    // protected array $parametros = [
    //     'limiteHorasDiarias' => ['tipo' => 'limite_horas', 'default' => 8],
    //     'otraPropiedad'      => ['tipo' => 'otro_tipo',    'default' => 0],
    // ];

    public function cargarParametros(): void
    {
        foreach ($this->parametros as $propiedad => $config) {
            $this->$propiedad = ParametroTemporal::obtener(
                $config['tipo'],
                $this->fecha,           // usa $this->fecha del componente
                $config['default']
            );
        }
    }

    public function guardarParametro(string $propiedad): void
    {
        $config = $this->parametros[$propiedad] ?? null;

        if (!$config) return;

        ParametroTemporal::guardar(
            $config['tipo'],
            $this->fecha,
            $this->$propiedad,
            auth()->id()
        );
    }
}