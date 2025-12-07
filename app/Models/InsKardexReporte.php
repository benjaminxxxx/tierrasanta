<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsKardexReporte extends Model
{
    use HasFactory;
    protected $table = 'ins_kardex_reportes';
    protected $fillable = [
        'nombre',
        'anio',
        'estado',
        'tipo_kardex'
    ];
    public function categorias()
    {
        return $this->hasMany(InsKardexReporteCategoria::class, 'reporte_id');
    }
    public function detalles()
    {
        return $this->hasMany(InsKardexReporteDetalle::class, 'reporte_id');
    }
}
