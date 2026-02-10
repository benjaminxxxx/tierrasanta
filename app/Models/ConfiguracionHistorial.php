<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionHistorial extends Model
{
    use HasFactory;

    protected $table = 'configuracion_historial';

    protected $fillable = [
        'configuracion_codigo',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'activo'
    ];

    protected $casts = [
        'valor' => 'decimal:4',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'activo' => 'boolean'
    ];

    public function configuracion()
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_codigo', 'codigo');
    }
}
