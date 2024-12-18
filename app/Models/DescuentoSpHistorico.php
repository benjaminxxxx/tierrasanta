<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescuentoSpHistorico extends Model
{
    use HasFactory;
    protected $table = 'descuento_sp_historicos';

    protected $fillable = [
        'descuento_codigo',
        'porcentaje',
        'porcentaje_65',
        'fecha_inicio',
        'fecha_fin',
    ];
    public function descuentoSp(){
        return $this->belongsTo(DescuentoSP::class,'descuento_codigo');
    }
}
