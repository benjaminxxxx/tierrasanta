<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaAsistencia extends Model
{
    use HasFactory;
    protected $fillable = [
        'grupo',
        'documento',
        'nombres',
        'total_horas',
        'mes',
        'orden',
        'anio',
    ];

    public function detalles()
    {
        return $this->hasMany(PlanillaAsistenciaDetalle::class);
    }
}
