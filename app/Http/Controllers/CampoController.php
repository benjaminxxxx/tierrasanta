<?php

namespace App\Http\Controllers;

use App\Models\Campo;
use App\Models\CampoCampania;
use Illuminate\Http\Request;

class CampoController extends Controller
{
    public function riego()
    {
        $campos = Campo::all();
        return view('campo.riego', [
            'campos' => $campos
        ]);
    }
    public function campos()
    {
        return view('campo.campos');
    }


    public function siembra()
    {
        return view('livewire.gestion-siembra.siembra-indice');
    }
    public function campania($campo = null)
    {
        if ($campo) {
            $campoExiste = Campo::find($campo);
            if (!$campoExiste) {
                return redirect()->back();
            }
        }

        return view('campo.campania', [
            'campo' => $campo
        ]);
    }
    public function campaniaxcampo($campania = null)
    {
        if ($campania) {
            $campaniaCampo = CampoCampania::find($campania);
            if (!$campaniaCampo) {

                return redirect()->route('campania.x.campo');
            }
        }
        return view('livewire.gestion-campania.campania-x-campo-indice', [
            'campaniaId' => $campania,
        ]);
    }
    public function guardarPosicion(Request $request, $nombre)
    {
        try {
            $validated = $request->validate([
                'pos_x' => 'required|integer',
                'pos_y' => 'required|integer',
            ]);

            $campo = Campo::find($nombre);
            if ($campo) {
                $grupo = $campo->grupo;
                if ($campo->orden == 1) {
                    // Obtener todos los campos del mismo grupo y ordenar por 'orden'
                    $camposDelGrupo = Campo::where('grupo', $grupo)->orderBy('orden')->get();

                    $x = $validated['pos_x'];
                    foreach ($camposDelGrupo as $index => $campoDelGrupo) {
                        // Actualizar 'pos_x' y calcular 'pos_y' para cada campo
                        $campoDelGrupo->update([
                            'pos_x' => $x,
                            'pos_y' => $validated['pos_y'] + ($index * 60)
                        ]);

                        $camposHijos = Campo::where('campo_parent_nombre', $campo->nombre)->count();


                        if ($camposHijos > 0) {
                            $this->actualizarCamposHijos($campoDelGrupo, $campoDelGrupo->pos_x, $campoDelGrupo->pos_y);
                        }
                    }
                    if ($camposDelGrupo->count() > 1) {
                        return response()->json([
                            'reload' => true
                        ]);
                    }

                } else {
                    // Actualizar solo el campo actual
                    $campo->pos_x = $validated['pos_x'];
                    $campo->pos_y = $validated['pos_y'];
                    $campo->save();
                }
            }


            return response()->json([
                'message' => 'Posición guardada correctamente',
                'campo' => $campo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al guardar la posición',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    protected function actualizarCamposHijos($campo, $posXPadre, $posYPadre)
    {
        // Obtener todos los campos hijos del campo actual
        $camposHijos = Campo::where('campo_parent_nombre', $campo->nombre)->get();

        // Actualizar la posición de cada campo hijo
        foreach ($camposHijos as $index => $campoHijo) {
            $campoHijo->update([
                'pos_x' => $posXPadre + (($index + 1) * 110), // Posición X ajustada
                'pos_y' => $posYPadre // Misma posición Y que el padre
            ]);
        }
    }

}
