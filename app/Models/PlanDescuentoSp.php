<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PlanDescuentoSp extends Model
{
    use HasFactory;
    protected $table = 'plan_sp_desc';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'codigo',
        'descripcion',
        'porcentaje',
        'porcentaje_65',
        'orden',
        'tipo'
    ];
    public static function buscarDescuentoSegun(string $codigo, int $mes, int $anio): ?PlanDescuentoSpHistorico
    {
        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();

        return PlanDescuentoSpHistorico::where('descuento_codigo', $codigo)
            ->where('fecha_inicio', '<=', $fechaReferencia)
            ->with('descuentoSp')
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }
}
