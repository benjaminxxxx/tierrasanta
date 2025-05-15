<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionBrotesXPiso extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_brotes_x_pisos';

    protected $fillable = [
        'campania_id',
        'fecha',
        'metros_cama',
        'evaluador',
        'empleado_id',
        'cuadrillero_id',
        'promedio_actual_brotes_2piso',
        'promedio_brotes_2piso_n_dias',
        'promedio_actual_brotes_3piso',
        'promedio_brotes_3piso_n_dias',
        'promedio_actual_total_brotes_2y3piso',
        'promedio_total_brotes_2y3piso_n_dias',
        'reporte_file'
    ];

    public function detalles()
    {
        return $this->hasMany(EvaluacionBrotesXPisoDetalle::class, 'brotes_x_piso_id');
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campania_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class, 'cuadrillero_id');
    }
    /**
     * Esta propiedad se utiliza en evaluacion infestacion cosecha
     * 
     */
    public function getTotalHectareaAttribute(){
        return $this->promedio_actual_total_brotes_2y3piso + $this->promedio_total_brotes_2y3piso_n_dias;
    }
}
