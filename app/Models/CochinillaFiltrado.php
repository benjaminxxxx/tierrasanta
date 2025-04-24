<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CochinillaFiltrado extends Model
{
    use HasFactory;

    protected $fillable = [
        'lote',
        'fecha_proceso',
        'kilos_ingresados',
        'primera',
        'segunda',
        'tercera',
        'piedra',
        'basura',
    ];
    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'lote', 'lote');
    }
    public function getPorcentajePrimeraAttribute()
    {
        return $this->primera / $this->kilos_ingresados * 100;
    }
    public function getPorcentajeSegundaAttribute()
    {
        return $this->segunda / $this->kilos_ingresados * 100;
    }
    public function getPorcentajeTerceraAttribute()
    {
        return $this->tercera / $this->kilos_ingresados * 100;
    }
    public function getPorcentajePiedraAttribute()
    {
        return $this->piedra / $this->kilos_ingresados * 100;
    }
    public function getPorcentajeBasuraAttribute()
    {
        return $this->basura / $this->kilos_ingresados * 100;
    }
}

