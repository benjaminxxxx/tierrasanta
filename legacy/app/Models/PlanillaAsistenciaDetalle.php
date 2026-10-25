<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaAsistenciaDetalle extends Model
{
    use HasFactory;
    protected $fillable = [
        'planilla_asistencia_id',
        'fecha',
        'tipo_asistencia',
        'horas_jornal',
    ];

    public function planillaAsistencia()
    {
        return $this->belongsTo(PlanillaAsistencia::class);
    }
}
