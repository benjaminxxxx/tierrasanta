<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidadoRiego extends Model
{
    use HasFactory;
    protected $table = 'consolidado_riegos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'regador_documento',
        'regador_nombre',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas_riego',
        'total_horas_jornal',
        'estado'
    ];
}
