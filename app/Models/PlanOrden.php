<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanOrden extends Model
{
     protected $table = 'plan_ordenes';

    protected $fillable = [
        'empleado_id',
        'anio',
        'mes',
        'orden',
    ];

    protected $casts = [
        'anio'  => 'integer',
        'mes'   => 'integer',
        'orden' => 'integer',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class, 'empleado_id');
    }
}
