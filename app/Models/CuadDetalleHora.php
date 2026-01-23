<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuadDetalleHora extends Model
{
    use HasFactory;

    protected $table = 'cuad_detalles_horas';

    protected $fillable = [
        'registro_diario_id',
        'campo_nombre',
        'codigo_labor',
        'hora_inicio',
        'hora_fin'
    ];

    // Relaciones
    public function registroDiario()
    {
        return $this->belongsTo(CuadRegistroDiario::class, 'registro_diario_id');
    }
     public function labores()
    {
        return $this->belongsTo(Labores::class, 'codigo_labor');
    }
}
