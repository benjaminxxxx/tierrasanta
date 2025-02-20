<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroProductividad extends Model
{
    use HasFactory;

    protected $fillable = [
        'labor_valoracion_id',
        'labor_id',
        'fecha',
        'campo',
        'kg_8',
        'valor_kg_adicional'
    ];

    public function valoracion()
    {
        return $this->belongsTo(LaborValoracion::class, 'labor_valoracion_id');
    }

    public function labor()
    {
        return $this->belongsTo(Labores::class, 'labor_id');
    }

    public function detalles()
    {
        return $this->hasMany(RegistroProductividadDetalle::class, 'registro_productividad_id');
    }
    public function bonos()
    {
        return $this->hasMany(RegistroProductividadBono::class, 'registro_productividad_id');
    }

    public function getDiaSemanaAttribute()
    {
        $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        $fecha = Carbon::parse((string)$this->fecha);

        // Obtener el índice del día (0 para domingo, 6 para sábado)
        $indiceDia = $fecha->dayOfWeek;

        // Retornar el día en español
        return $dias[$indiceDia];
    }
    public function getFechaCortaAttribute()
    {
        $fecha = Carbon::parse((string)$this->fecha);
    
        // Establecer el idioma a español para los nombres de meses
        Carbon::setLocale('es');
    
        // Usar strftime para dar formato "15-Jun" o "25-Nov"
        return $fecha->translatedFormat('d-M');
    }
}
