<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class CuadRegistroDiario extends Model
{
    use HasFactory;
    protected $table = 'cuad_registros_diarios';
    protected $fillable = [
        'cuadrillero_id',
        'fecha',
        'costo_personalizado_dia',
        'total_bono',
        'total_horas',
        //'costo_dia',
        'esta_pagado',
        'codigo_grupo',
        'bono_esta_pagado',
        'tramo_pagado_jornal_id',
        'tramo_pagado_bono_id',
        'tramo_laboral_id',
        //nuevo campo triggeado
        'jornal_aplicado',
        'horas_destajo'
    ];
    protected $casts = [
        'asistencia' => 'boolean',
        'fecha' => 'date',
        'esta_pagado' => 'boolean',
        'bono_esta_pagado' => 'boolean',
    ];
    /**
     * Calcula el costo del jornal considerando solo horas NO a destajo.
     * 
     * Fórmula:
     * - Horas jornaleadas = total_horas - horas_destajo
     * - Costo = (horas_jornaleadas / 8) * jornal_aplicado
     * 
     * @return float
     */
    public function getCostoDiaAttribute(): float
    {
        $jornal = $this->costo_personalizado_dia ?: (float) $this->jornal_aplicado;
        
        // Solo se pagan las horas que NO son a destajo
        $horasJornaleadas = max(0, $this->total_horas - $this->horas_destajo);
        
        return ($horasJornaleadas / 8) * $jornal;
    }

    /**
     * Calcula el costo total del día.
     * 
     * Componentes:
     * - Costo jornal (horas normales)
     * - Bonos (estándar + destajo)
     * 
     * @return float
     */
    public function getTotalCostoAttribute(): float
    {
        return $this->costo_dia + $this->total_bono;
    }
    // Relaciones
    public function tramoLaboral()
    {
        return $this->belongsTo(CuadTramoLaboral::class, 'tramo_laboral_id');
    }
    public function grupo()
    {
        return $this->belongsTo(CuaGrupo::class, 'codigo_grupo');
    }
    public function actividadesBonos()
    {
        return $this->hasMany(CuadActividadBono::class, 'registro_diario_id');
    }
    public function cuadrillero()
    {
        return $this->belongsTo(Cuadrillero::class);
    }

    public function detalleHoras()
    {
        return $this->hasMany(CuadDetalleHora::class, 'registro_diario_id');
    }


    // Accesor para total_costo calculado


    public function getCoincideTotalHorasAttribute()
    {

        $totalCalculado = $this->detalleHoras->reduce(function ($carry, $detalle) {
            $inicio = Carbon::createFromFormat('H:i:s', $detalle->hora_inicio);
            $fin = Carbon::createFromFormat('H:i:s', $detalle->hora_fin);
            return $carry + ($inicio->diffInMinutes($fin) / 60);
        }, 0);
        return round($this->total_horas, 2) === round($totalCalculado, 2);
    }

}
