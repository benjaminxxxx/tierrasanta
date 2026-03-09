<?php
// app/Procesos/Cuadrillas/ReemplazarCuadrillero.php

namespace App\Procesos\Cuadrillas;

//use App\Services\Cuadrillas\CuadrilleroServicio;
use App\Services\Cuadrillas\RegistroDiarioServicio;
use App\Services\Cuadrillas\TramoCuadrilleroServicio;
use Illuminate\Support\Facades\DB;

class ReemplazarCuadrillero
{
    public function __construct(
        //private CuadrilleroServicio     $cuadrilleroServicio,
        private RegistroDiarioServicio  $registroDiarioServicio,
        private TramoCuadrilleroServicio $tramoCuadrilleroServicio,
    ) {}

    public function ejecutar(int $tramoLaboralId, int $anteriorId, int $nuevoId): void
    {
        // 1. No reemplazar con el mismo
        if ($anteriorId === $nuevoId) {
            throw new \InvalidArgumentException('El cuadrillero nuevo es el mismo que el actual.');
        }

        // 2. Validar que el nuevo no exista ya en el mismo tramo
        $yaExisteEnTramo = $this->tramoCuadrilleroServicio
            ->existeEnTramo($tramoLaboralId, $nuevoId);

        if ($yaExisteEnTramo) {
            throw new \InvalidArgumentException('El cuadrillero ya pertenece a este tramo laboral.');
        }

        DB::transaction(function () use ($tramoLaboralId, $anteriorId, $nuevoId) {
            // 3. Reemplazar en registros diarios (solo del tramo indicado)
            $this->registroDiarioServicio
                ->reemplazarCuadrillero($tramoLaboralId, $anteriorId, $nuevoId);

            // 4. Reemplazar en tramo_cuadrilleros
            $this->tramoCuadrilleroServicio
                ->reemplazarCuadrillero($tramoLaboralId, $anteriorId, $nuevoId);
        });
    }
}