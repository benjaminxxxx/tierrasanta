<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiario extends Model
{
    use HasFactory;

    // Definir los campos que se pueden asignar de forma masiva
    protected $fillable = [
        'documento',
        'empleado_nombre',
        'asistencia',
        'tipo_trabajador',
        'total_horas',
        'fecha',
        'orden',
        'bono_productividad'
    ];

    public function detalles()
    {
        return $this->hasMany(ReporteDiarioDetalle::class);
    }

}
