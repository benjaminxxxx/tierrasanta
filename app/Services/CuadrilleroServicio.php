<?php

namespace App\Services;
use App\Models\Cuadrillero;

class CuadrilleroServicio
{
    // Leer un cuadrillero por ID
    public function leer($id)
    {
        return Cuadrillero::find($id);
    }

    // Insertar un nuevo cuadrillero
    public function insertar(array $data)
    {
        return Cuadrillero::create($data);
    }

    // Editar un cuadrillero existente
    public function editar($id, array $data)
    {
        $cuadrillero = Cuadrillero::find($id);
        if ($cuadrillero) {
            $cuadrillero->update($data);
            return $cuadrillero;
        }
        return null;
    }

    // Eliminar un cuadrillero por ID
    public function eliminar($id)
    {
        $cuadrillero = Cuadrillero::find($id);
        if ($cuadrillero) {
            $cuadrillero->delete();
            return true;
        }
        return false;
    }

    // Guardar: decide entre insertar o editar según si hay ID
    public function guardar(array $data, $id = null)
    {
        if ($id) {
            return $this->editar($id, $data);
        } else {
            return $this->insertar($data);
        }
    }
}