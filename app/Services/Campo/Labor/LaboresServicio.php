<?php

namespace App\Services\Campo\Labor;

use App\Models\Labores;

class LaboresServicio
{
    public static function selectLabores(){
        return Labores::get()->map(function($labor){
             return [
                "id"=> $labor->id,
                "name"=> "{$labor->codigo} - {$labor->nombre_labor}"
             ];
        });
    }
}