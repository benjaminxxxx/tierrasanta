<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioDetalle extends Model
{
    use HasFactory;
    protected $fillable = [
        'reporte_diario_id',
        'campo',
        'labor',
        'hora_inicio',
        'hora_salida',
        'produccion',
        'costo_bono'
    ];
    public function reporteDiario()
    {
        return $this->belongsTo(ReporteDiario::class,'reporte_diario_id');
    }
    public function labores()
    {
        return $this->belongsTo(Labores::class,'labor');
    }
}
