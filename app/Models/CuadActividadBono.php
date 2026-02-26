<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadActividadBono extends Model
{
    use HasFactory;

    protected $table = 'cuad_bonos_actividades';

    protected $fillable = [
        'registro_diario_id',
        'actividad_id',
        'metodo_id',
        'total_bono'
    ];

    // Relación inversa
    public function registroDiario()
    {
        return $this->belongsTo(CuadRegistroDiario::class, 'registro_diario_id');
    }
    public function metodo()
    {
        return $this->belongsTo(ActividadMetodo::class, 'metodo_id');
    }
    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }
    // Relación con producciones
    public function producciones()
    {
        return $this->hasMany(CuadActividadProduccion::class, 'actividad_bono_id');
    }
}
