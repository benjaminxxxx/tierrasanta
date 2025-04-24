<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CochinillaVenteado extends Model
{
    use HasFactory;
    protected $table = 'cochinilla_venteados';

    protected $fillable = [
        'lote',
        'fecha_proceso',
        'kilos_ingresado',
        'limpia',
        'basura',
        'polvillo',
    ];
    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'lote', 'lote');
    }
    public function getPorcentajeLimpiaAttribute()
    {
        return $this->limpia / $this->kilos_ingresado * 100;
    }
    public function getPorcentajeBasuraAttribute()
    {
        return $this->basura / $this->kilos_ingresado * 100;
    }
    public function getPorcentajePolvilloAttribute()
    {
        return $this->polvillo / $this->kilos_ingresado * 100;
    }
}
