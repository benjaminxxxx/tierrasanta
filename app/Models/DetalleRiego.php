<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleRiego extends Model
{
    use HasFactory;
    protected $table = 'detalle_riegos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'campo',
        'regador',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas',
    ];
}