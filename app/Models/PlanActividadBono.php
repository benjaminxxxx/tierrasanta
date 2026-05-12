<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanActividadBono extends Model
{
    use HasFactory;

    protected $table = 'plan_actividad_bonos';

    protected $fillable = [
        'registro_diario_id',
        'actividad_id',
        'metodo_id',
        'bono_manual',
        'total_bono',
    ];
    protected $casts = [
        'bono_manual' => 'boolean',
    ];
    // Relaciones
    public function registroDiario()
    {
        return $this->belongsTo(PlanRegistroDiario::class, 'plan_registros_diarios');
    }
    public function metodo()
    {
        return $this->belongsTo(ActividadMetodo::class, 'metodo_id');
    }
    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }
    public function producciones()
    {
        return $this->hasMany(PlanActividadProduccion::class, 'actividad_bono_id');
    }
}
