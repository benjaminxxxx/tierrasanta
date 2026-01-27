<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ReporteDiarioRiego extends Model
{
    use HasFactory;

    protected $table = 'reg_registro_diario';

    protected $fillable = [
        'campo',
        'hora_inicio',
        'hora_fin',
        //'total_horas',
        'documento',
        'regador',
        'fecha',
        'sh',
        'tipo_labor',
        'descripcion',
        'campo_campania_id',
    ];
    /**
     * Atributo virtual para obtener el total de horas en formato decimal.
     * Se accede como: $reporte->total_horas_decimal
     */
    protected function totalHoras(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->hora_inicio || !$this->hora_fin) {
                    return 0;
                }

                $inicio = Carbon::parse($this->hora_inicio);
                $fin = Carbon::parse($this->hora_fin);

                // Si por error la hora fin es menor (cruce de día), manejamos el caso
                if ($fin->lt($inicio)) {
                    $fin->addDay();
                }

                $minutos = $inicio->diffInMinutes($fin);

                return round($minutos / 60, 2);
            },
        );
    }
}
