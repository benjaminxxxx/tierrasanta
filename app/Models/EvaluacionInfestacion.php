<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInfestacion extends Model
{
    use HasFactory;
    protected $table = "evaluacion_infestaciones";
    protected $fillable = [
        'fecha',
        'campo_campania_id',
    ];
    public function detalles()
    {
        return $this->hasMany(EvaluacionInfestacionDetalle::class);
    }
}
