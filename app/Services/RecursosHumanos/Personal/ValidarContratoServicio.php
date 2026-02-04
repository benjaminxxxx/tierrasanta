<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\PlanDescuentoSp;
use App\Models\PlanEmpleado;
use Illuminate\Validation\ValidationException;

class ValidarContratoServicio
{
    /**
     * Valida todos los datos del Excel fila por fila.
     */
    public static function validarDatosExcel(array $dataExcel)
    {
        // 1. Cargamos catálogos una sola vez para validación rápida
        $tiposPlanilla = ['agraria', 'oficina'];
        $codigoSistemaPensiones = PlanDescuentoSp::pluck('codigo')->toArray();
        $estados = ['Continúa', 'BAJA'];

        // 2. Cargamos empleados indexados por documento (array real)
        $mapeoReal = PlanEmpleado::all()->keyBy('documento');

        foreach ($dataExcel as $indice => $fila) {
            // El índice suele empezar en 0, sumamos 2 (1 por base 0, 1 por encabezado Excel)
            $numeroFila = $indice + 2; 

            // --- Validación: Tipo de planilla ---
            if (!in_array(mb_strtolower($fila['planilla']), $tiposPlanilla)) {
                self::lanzarError("Error en la fila {$numeroFila}: Tipo de planilla inválido '{$fila['planilla']}'.");
            }

            // --- Validación: Sistema de pensiones ---
            if (!in_array($fila['sistema'], $codigoSistemaPensiones)) {
                self::lanzarError("Error en la fila {$numeroFila}: Sistema de pensiones '{$fila['sistema']}' no existe en el catálogo.");
            }

            // --- Validación: Estado ---
            if (!in_array($fila['estado'], $estados)) {
                self::lanzarError("Error en la fila {$numeroFila}: Estado '{$fila['estado']}' no es válido (Debe ser Continúa o BAJA).");
            }

            // --- Validación: Nombres y Apellidos contra la DB ---
            self::validarIdentidad($fila, $mapeoReal, $numeroFila);
        }

        return true;
    }

    /**
     * Compara los nombres del Excel contra los datos reales de la base de datos.
     */
    private static function validarIdentidad(array $filaExcel, $mapeoReal, int $numeroFila)
    {
        $dni = trim($filaExcel['dni']);

        // 1. Existe el DNI?
        if (!$mapeoReal->has($dni)) {
            self::lanzarError("Error en la fila {$numeroFila}: El DNI {$dni} no está registrado como empleado.");
        }

        $empleadoDB = $mapeoReal->get($dni);
        $errores = [];

        // 2. Comparación estricta normalizada
        if (self::normalizar($filaExcel['paterno']) !== self::normalizar($empleadoDB->apellido_paterno)) {
            $errores[] = "Paterno (Excel: {$filaExcel['paterno']} | DB: {$empleadoDB->apellido_paterno})";
        }

        if (self::normalizar($filaExcel['materno']) !== self::normalizar($empleadoDB->apellido_materno)) {
            $errores[] = "Materno (Excel: {$filaExcel['materno']} | DB: {$empleadoDB->apellido_materno})";
        }

        if (self::normalizar($filaExcel['nombres']) !== self::normalizar($empleadoDB->nombres)) {
            $errores[] = "Nombres (Excel: {$filaExcel['nombres']} | DB: {$empleadoDB->nombres})";
        }

        if (!empty($errores)) {
            $detalle = implode(', ', $errores);
            self::lanzarError("Error en la fila {$numeroFila} (DNI {$dni}): Datos de identidad no coinciden. Detalles: {$detalle}");
        }
    }

    private static function normalizar($texto)
    {
        return mb_strtoupper(trim($texto ?? ''), 'UTF-8');
    }

    private static function lanzarError($mensaje)
    {
        throw ValidationException::withMessages(['excel' => $mensaje]);
    }
}