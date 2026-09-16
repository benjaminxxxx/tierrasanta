<?php

namespace App\Services\InformacionGeneral;

use App\Models\Proveedor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProveedorServicio
{
    /**
     * Crea o actualiza un registro de Proveedor.
     *
     * @param array $datos
     * @param int|null $proveedorId
     * @return Proveedor
     * @throws \Throwable
     */
    public function guardar(array $datos, ?int $proveedorId = null): Proveedor
    {
        return DB::transaction(function () use ($datos, $proveedorId) {
            $userId = Auth::id();

            // Evalúa si buscar por ID de proveedor o por el id de la persona asociada
            $criterioBusqueda = $proveedorId 
                ? ['id' => $proveedorId] 
                : ['persona_id' => $datos['persona_id']];

            return Proveedor::updateOrCreate(
                $criterioBusqueda,
                [
                    'persona_id'                  => $datos['persona_id'],
                    'tipo_contribuyente'          => $datos['tipo_contribuyente'] ?? null,
                    'estado_contribuyente'        => $datos['estado_contribuyente'] ?? null,
                    'estado_domicilio'            => $datos['estado_domicilio'] ?? null,
                    'condicion'                   => $datos['condicion'] ?? null,
                    'fecha_inscripcion'           => $datos['fecha_inscripcion'] ?? null,
                    'fecha_inicio_actividades'    => $datos['fecha_inicio_actividades'] ?? null,
                    'ciiu'                        => $datos['ciiu'] ?? null,
                    'actividad_comercio_exterior' => $datos['actividad_comercio_exterior'] ?? null,
                    'es_agente_retencion'         => $datos['es_agente_retencion'] ?? false,
                    'es_buen_contribuyente'       => $datos['es_buen_contribuyente'] ?? false,
                    'verificado'                  => $datos['verificado'] ?? false,
                    'verificado_at'               => !empty($datos['verificado']) ? now() : null,
                    'editado_por'                 => $userId,
                ]
            );
        });
    }
}