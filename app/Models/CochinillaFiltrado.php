<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CochinillaFiltrado extends Model
{
    use HasFactory;

    protected $fillable = [
        'cochinilla_ingreso_id',
        'lote',
        'fecha_proceso',
        'kilos_ingresados',
        'primera',
        'segunda',
        'tercera',
        'piedra',
    ];
    protected $appends = ['basura'];

    public function getBasuraAttribute(): float
    {
        return round(
            $this->kilos_ingresados
            - (
                $this->primera
                + $this->segunda
                + $this->tercera
                + $this->piedra
            ),
            2
        );
    }
    public function getTotalAttribute()
    {
        return $this->primera + $this->segunda + $this->tercera + $this->piedra + $this->basura;
    }
    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'cochinilla_ingreso_id');
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

