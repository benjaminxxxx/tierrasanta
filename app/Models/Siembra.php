<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siembra extends Model
{
    use HasFactory;

    protected $table = 'siembras';

    protected $fillable = [
        'campo_nombre',     // Relación con el campo
        'fecha_siembra',    
        'fecha_renovacion', // Fecha en la que se limpia el campo para resembrar
        'variedad_tuna',
        'sistema_cultivo',
        'tipo_cambio',
    ];

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }
}
