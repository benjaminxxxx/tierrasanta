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
        'descuento_horas_almuerzo',
        'no_acumular_horas',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'total_horas_riego',
        'total_horas_observaciones',
        'minutos_acumulados',
        'minutos_utilizados',
        'total_horas_jornal',
        'estado',//obsoleto

        'trabajador_id',
        'trabajador_type',
        'minutos_regados',
        'minutos_jornal'
    ];
    protected $casts = [
        'descuento_horas_almuerzo' => 'boolean',
        'no_acumular_horas' => 'boolean'
    ];
    protected $appends = [
        'alias_origen',
        'trabajador_nombre',
        'horas_jornal',
        'horas_acumuladas',
        'horas_regados'
    ];
    public function getHorasJornalAttribute(): ?string
    {
        return $this->minutos_jornal/60;
    }
    public function getHorasAcumuladasAttribute(): ?string
    {
        return $this->minutos_acumulados/60;
    }
    public function getHorasRegadosAttribute(): ?string
    {
        return $this->minutos_regados/60;
    }
    
    public function getAliasOrigenAttribute(): ?string
    {
        return match ($this->trabajador_type) {
            'App\Models\PlanEmpleado' => 'PLANILLA',
            'App\Models\Cuadrillero' => 'CUADRILLA',
            default => null,
        };
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
    }
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
