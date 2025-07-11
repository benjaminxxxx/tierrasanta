<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuadRegistroDiario extends Model
{
    use HasFactory;

    protected $table = 'cuad_registros_diarios';

    protected $fillable = [
        'cuadrillero_id',
        'fecha',
        'costo_personalizado_dia',
        'asistencia',
        'total_bono',
        'total_horas',
        'costo_dia',
    ];

    protected $casts = [
        'asistencia' => 'boolean',
        'fecha' => 'date',
    ];

    // Relaciones
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function detalleHoras()
    {
        return $this->hasMany(CuadDetalleHora::class, 'registro_diario_id');
    }

    // Accesor para total_costo calculado
    public function getTotalCostoAttribute()
    {
        return $this->total_bono + $this->costo_dia;
    }
}
