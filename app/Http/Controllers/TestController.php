<?php

namespace App\Http\Controllers;

use App\Models\CampoCampania;
use App\Services\Campania\CampaniaServicio;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function mano_obra(){
        $campania = CampoCampania::where('campo','2')->orderBy('fecha_inicio','desc')->first();
        app(CampaniaServicio::class)->obtenerCostosManoObra($campania);
    }
}
