<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Código de labor aceptado para un campo en un día (ver CampoActividadProceso). */
class CampoActividadAceptada extends Model
{
    protected $table = 'campo_actividades_aceptadas';

    protected $fillable = ['campo_nombre', 'fecha', 'codigo_labor', 'aceptado_por'];

    protected $casts = ['fecha' => 'date'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'aceptado_por');
    }
}
