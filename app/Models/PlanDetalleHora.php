<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanDetalleHora extends Model
{
    use HasFactory;
    protected $table = 'plan_detalles_horas';
    protected $fillable = [
        'plan_reg_dia_id',
        'campo_nombre',
        'codigo_labor',
        'hora_inicio',
        'hora_fin',
        'orden'
    ];
    public function registroDiario(){
        return $this->belongsTo(PlanRegistroDiario::class,'plan_reg_dia_id');
    }
    public function labores(){
        return $this->belongsTo(Labores::class,'codigo_labor');
    }
}
