<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanDescuentoSpHistorico extends Model
{
    use HasFactory;
    protected $table = 'plan_sp_desc_hist';

    protected $fillable = [
        'descuento_codigo',
        'porcentaje',
        'porcentaje_65',
        'fecha_inicio',
        'fecha_fin',
    ];
    public function descuentoSp(){
        return $this->belongsTo(PlanDescuentoSP::class,'descuento_codigo');
    }
}
