<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuadDetalleHora extends Model
{
    use HasFactory;

    protected $table = 'cuad_detalle_horas';

    protected $fillable = [
        'registro_diario_id',
        'actividad_id',
        'campo_nombre',
        'hora_inicio',
        'hora_fin',
        'produccion',
        'costo_bono',
    ];

    // Relaciones
    public function registroDiario()
    {
        return $this->belongsTo(CuadRegistroDiario::class, 'registro_diario_id');
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }
}
