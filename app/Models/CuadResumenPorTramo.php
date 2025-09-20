<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuadResumenPorTramo extends Model
{
    use HasFactory;

    protected $table = 'cuad_resumen_por_tramo';

    protected $fillable = [
        'grupo_codigo',
        'color',
        'tipo',
        'descripcion',
        'condicion',
        'fecha',
        'recibo',
        'deuda_actual',
        'deuda_acumulada',
        'tramo_id',
        'tramo_acumulado_id',
    ];

    public function tramo()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_id');
    }

    public function tramoAcumulado()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_acumulado_id');
    }
}
