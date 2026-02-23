<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTipoSuspension extends Model
{
    protected $table = 'plan_tipos_suspension';
    protected $fillable = [
        'codigo',
        'grupo',
        'descripcion'
    ];
    
}
