<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiarioRiego extends Model
{
    use HasFactory;

    protected $table = 'reg_registro_diario';

    protected $fillable = [
        'campo',
        'hora_inicio',
        'hora_fin',
        'total_horas',
        'documento',
        'regador',
        'fecha',
        'sh',
        'tipo_labor',
        'descripcion',
        'campo_campania_id',
    ];

}
