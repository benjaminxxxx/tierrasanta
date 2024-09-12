<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorasAcumuladas extends Model
{
    use HasFactory;
    protected $fillable = [
        'documento',
        'fecha_acumulacion',
        'fecha_uso',
        'minutos_acomulados'
    ];

    public function getHoraAttribute()
    {
        $horas = floor($this->minutos_acomulados/ 60);
        $minutos_restantes = $this->minutos_acomulados % 60;
        return sprintf('%02d:%02d', $horas, $minutos_restantes);
    }
}
