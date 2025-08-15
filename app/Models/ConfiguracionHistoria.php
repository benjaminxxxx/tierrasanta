<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionHistoria extends Model
{
    use HasFactory;
    protected $table = 'configuracion_historias';

    protected $fillable = [
        'codigo',
        'mes_vigencia',
        'anio_vigencia',
        'valor',
    ];
}
