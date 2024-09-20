<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioCuadrilla extends Model
{
    use HasFactory;
    protected $fillable = [
        'numero_cuadrilleros',
        'total_horas',
        'fecha'
    ];
    public function detalles()
    {
        return $this->hasMany(ReporteDiarioCuadrillaDetalle::class,'reporte_diario_id');
    }
}
