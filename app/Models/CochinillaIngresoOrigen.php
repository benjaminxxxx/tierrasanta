<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kg de un retorno de infestadores que pertenecen a un campo/campaña de origen (ver CochinillaRecuperacionProceso). */
class CochinillaIngresoOrigen extends Model
{
    protected $table = 'cochinilla_ingreso_origenes';

    protected $fillable = ['cochinilla_ingreso_id', 'campo_origen', 'campania_origen_id', 'kg', 'kg_madres', 'porcentaje_sugerido', 'registrado_por'];

    protected $casts = ['kg' => 'float', 'kg_madres' => 'float', 'porcentaje_sugerido' => 'float'];

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(CochinillaIngreso::class, 'cochinilla_ingreso_id');
    }

    public function campaniaOrigen(): BelongsTo
    {
        return $this->belongsTo(CampoCampania::class, 'campania_origen_id');
    }
}
