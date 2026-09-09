<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsolidadoRiego extends Model
{
    use HasFactory;
    protected $table = 'reg_resumen';

    /**
     * Atributos asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'regador_documento',//obsoleto
        'regador_nombre',//obsoleto
        //'descuento_horas_almuerzo', //deprecado
        'no_acumular_horas',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas_observaciones',
        'minutos_acumulados',
        'minutos_utilizados',
        'estado',//obsoleto

        'trabajador_id',
        'trabajador_type',
        'minutos_regados',
        'minutos_jornal',
        'sincronizado',
        'hora_inicio_almuerzo',
        'hora_fin_almuerzo',
        'explicacion_jornal_computable',
    ];
    protected $casts = [
        //'descuento_horas_almuerzo' => 'boolean',
        'no_acumular_horas' => 'boolean',
        'sincronizado' => 'boolean',
        'explicacion_jornal_computable' => 'array',
    ];
    protected $appends = [
        'alias_origen',
        'trabajador_nombre',
        'horas_jornal',
        'horas_acumuladas',
        'horas_regados'
    ];
    public function getJornalComputableAttribute(): float
    {
        return round($this->registrosDiarios->sum('horas_ponderadas'), 2);
    }
    public function getAliasOrigenAttribute(): ?string
    {
        return match ($this->trabajador_type) {
            'App\Models\PlanEmpleado' => 'PLANILLA',
            'App\Models\Cuadrillero' => 'CUADRILLA',
            default => null,
        };
    }
    public function getHorasJornalAttribute(): ?string
    {
        return $this->minutos_jornal / 60;
    }
    public function getHorasAcumuladasAttribute(): ?string
    {
        return $this->minutos_acumulados / 60;
    }
    public function getHorasRegadosAttribute(): ?string
    {
        return $this->minutos_regados / 60;
    }


    public function getTrabajadorNombreAttribute()
    {
        // Si no hay relación, retornamos el nombre base (regador_nombre)
        if (!$this->trabajador_type || !$this->trabajador_id) {
            return $this->regador_nombre;
        }

        // Instanciar el modelo desde el morph
        $model = app($this->trabajador_type)::find($this->trabajador_id);

        // Si no existe en BD, retornar el nombre base
        if (!$model) {
            return $this->regador_nombre;
        }

        // Según el tipo, devolver el atributo correcto
        if ($this->trabajador_type === \App\Models\PlanEmpleado::class) {
            return $model->nombre_completo; // campo PlanEmpleado
        }

        if ($this->trabajador_type === \App\Models\Cuadrillero::class) {
            return $model->nombres; // campo Cuadrillero
        }

        // Fallback seguro
        return $this->regador_nombre;
    }
    // En ConsolidadoRiego
    public function getMinutosDisponiblesAttribute(): int
    {
        // 1. Sumar minutos acumulados solo de fechas estrictamente anteriores
        $acumulado = self::where('trabajador_type', $this->trabajador_type)
            ->where('trabajador_id', $this->trabajador_id)
            ->where('fecha', '<', $this->fecha) // ⚠️ Filtro por fecha previa
            ->sum('minutos_acumulados');

        // 2. Sumar minutos consumidos provenientes de orígenes previos a esta fecha
        $utilizado = AcumulacionUso::whereHas('consolidadoOrigen', function ($q) {
            $q->where('trabajador_type', $this->trabajador_type)
                ->where('trabajador_id', $this->trabajador_id)
                ->where('fecha', '<', $this->fecha); // ⚠️ Filtro por fecha previa
        })->sum('minutos_consumidos');

        return max(0, $acumulado - $utilizado);
    }

    public function getDisponibleFormateadoAttribute(): string
    {
        $minutos = $this->minutos_disponibles;

        if ($minutos <= 0) {
            return "0min";
        }

        $horas = intdiv($minutos, 60);
        $mins = $minutos % 60;

        return $horas > 0
            ? "{$horas}h {$mins}min"
            : "{$mins}min";
    }
    /*
    public function getMinutosDisponiblesAttribute(): int
    {
        $acumulado = self::where('trabajador_type', $this->trabajador_type)
            ->where('trabajador_id', $this->trabajador_id)
            ->sum('minutos_acumulados');

        $utilizado = AcumulacionUso::whereHas('consolidadoOrigen', function ($q) {
            $q->where('trabajador_type', $this->trabajador_type)
                ->where('trabajador_id', $this->trabajador_id);
        })
            ->sum('minutos_consumidos');

        return max(0, $acumulado - $utilizado);
    }
    public function getDisponibleFormateadoAttribute(): string
    {
        $minutos = $this->minutos_disponibles;
        $horas = intdiv($minutos, 60);
        $mins = $minutos % 60;

        return $horas > 0
            ? "{$horas}h {$mins}min"
            : "{$mins}min";
    }*/
    public function registrosDiarios()
    {
        return $this->hasMany(ReporteDiarioRiego::class, 'consolidado_id');
    }
    public function getRegistroDiarioAcumuladoAttribute()
    {
        return $this->registrosDiarios()->where('por_acumulacion', true)->first();

    }
    public function trabajador()
    {
        return $this->morphTo();
    }
}
