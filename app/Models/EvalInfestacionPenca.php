<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvalInfestacionPenca extends Model
{
    protected $fillable = [
        'campo_campania_id',
        'numero_penca',

        'eval_primera_piso_2',
        'eval_primera_piso_3',

        'eval_segunda_piso_2',
        'eval_segunda_piso_3',

        'eval_tercera_piso_2',
        'eval_tercera_piso_3',
    ];

}
