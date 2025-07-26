<?php

namespace App\Services\Cuadrilla;

use App\Models\CuaGrupo;
use Exception;

class GrupoServicio
{
      /**
     * Crea o actualiza un grupo de cuadrilla.
     *
     * @param array $data Datos del grupo (nombre, código, color, modalidad_pago, costo_dia_sugerido).
     * @param string|null $codigo Código del grupo a actualizar. Si es null, se creará uno nuevo.
     * @return CuaGrupo
     * @throws Exception Si el código o nombre ya están en uso.
     */
    public static function guardarGrupo(array $data, $codigo = null)
    {
        // Parche: asignar color por defecto si no hay
        $data['color'] = $data['color'] ?? '#7b7b7b';

        if ($codigo) {
            // Actualizar registro existente
            $grupo = CuaGrupo::where('codigo', $codigo)->firstOrFail();

            $existeCodigo = CuaGrupo::where('codigo', $data['codigo'])->exists();
            if ($existeCodigo && ($codigo!=$data['codigo'])) {
                throw new Exception("El código ya está siendo usado.");
            }
            
            $existeNombre = CuaGrupo::where('nombre', $data['nombre'])
                ->where('codigo', '!=', $codigo)
                ->exists();

            if ($existeNombre) {
                throw new Exception("El nombre ya está siendo usado por otro grupo.");
            }

            $grupo->update($data);
            return $grupo;
        } else {
            // Validar código único
            $existeCodigo = CuaGrupo::where('codigo', $data['codigo'])->exists();
            if ($existeCodigo) {
                throw new Exception("El código ya está siendo usado.");
            }

            // Validar nombre único
            $existeNombre = CuaGrupo::where('nombre', $data['nombre'])->exists();
            if ($existeNombre) {
                throw new Exception("El nombre ya está siendo usado.");
            }

            return CuaGrupo::create($data);
        }
    }
}
