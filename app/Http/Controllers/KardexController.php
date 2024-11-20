<?php

namespace App\Http\Controllers;

use App\Models\Kardex;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function lista(){
        return view('kardex.lista');
    }
    public function ver($id){

        $Kardex = Kardex::find($id);

        if(!$Kardex){
            return redirect()->route('kardex.lista');
        }
        return view('kardex.ver',[
            'id'=>$id
        ]);
    }
}
