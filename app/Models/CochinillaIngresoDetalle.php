<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CochinillaIngresoDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'cochinilla_ingreso_id',
        'sublote_codigo',
        'fecha',
        'total_kilos',
        'observacion',
    ];

    public function ingreso()
    {
        return $this->belongsTo(CochinillaIngreso::class, 'cochinilla_ingreso_id');
    }

    public function observacionRelacionada()
    {
        return $this->belongsTo(CochinillaObservacion::class, 'observacion', 'codigo');
    }
}
