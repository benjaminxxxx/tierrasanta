<?php

namespace App\Services\Riego;

use App\Services\Campo\Gestion\CampoServicio;
use Exception;

// app/Services/Riego/ValidacionJornadaServicio.php
class ValidacionJornadaServicio
{
    // Sin transaction, solo validaciones y lanzar excepciones
    
    public function validarCampos(array $data): array
    {
        $nombresCampos = collect($data)->pluck(0)->filter()->unique()->toArray();
        $validacion = CampoServicio::validarCamposDesdeExcel($nombresCampos);

        if (!empty($validacion['invalidos'])) {
            throw new Exception("Campos inválidos: " . implode(', ', $validacion['invalidos']));
        }

        return $validacion['filtro'];
    }

    public function validarLimiteJornal(int $minutosActuales, int $minutosAAgregar, int $limite = 480): void
    {
        $total = $minutosActuales + $minutosAAgregar;
        if ($total > $limite) {
            $exceso = $total - $limite;
            throw new Exception(
                "Se excede el límite de 8h. " .
                "Actual: " . $this->formatear($minutosActuales) . ", " .
                "A agregar: " . $this->formatear($minutosAAgregar) . ", " .
                "Exceso: " . $this->formatear($exceso)
            );
        }
    }

    private function formatear(int $minutos): string
    {
        return intdiv($minutos, 60) . "h " . ($minutos % 60) . "m";
    }
}