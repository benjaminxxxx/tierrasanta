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
}
