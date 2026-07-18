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
        'polvillo',
    ];
    public function getBasuraAttribute(): float
    {
        return round(
            $this->kilos_ingresado
            - (
                $this->limpia
                + $this->polvillo
            ),
            2
        );
    }

    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'lote', 'lote');
    }
    public function getPorcentajeLimpiaAttribute()
    {
        if ((float) $this->kilos_ingresado == 0) {
            return 0;
        }
        return ($this->limpia / $this->kilos_ingresado) * 100;
    }

    public function getPorcentajeBasuraAttribute()
    {
        if ((float) $this->kilos_ingresado == 0) {
            return 0;
        }
        return ($this->basura / $this->kilos_ingresado) * 100;
    }

    public function getPorcentajePolvilloAttribute()
    {
        if ((float) $this->kilos_ingresado == 0) {
            return 0;
        }
        return ($this->polvillo / $this->kilos_ingresado) * 100;
    }
    public function getTotalAttribute()
    {
        return $this->polvillo + $this->basura + $this->limpia;
    }
}
