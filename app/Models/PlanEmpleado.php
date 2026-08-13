<?php

namespace App\Models;

use App\Domain\DerechoHabiente\EmpleadoAsignacionFamiliarService;
use Auth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanEmpleado extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'plan_empleados';
    protected $fillable = [
        'uuid',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'documento',
        'fecha_ingreso',
        'comentarios',
        'email',
        'numero',
        'fecha_nacimiento',
        'direccion',
        'genero',
        'orden',
        'creado_por',
        'actualizado_por',
        'eliminado_por',
    ];
    public function derechoHabientes()
    {
        return $this->hasMany(EmpleadoDerechoHabiente::class, 'empleado_id');
    }
    public function contratos()
    {
        return $this->hasMany(PlanContrato::class, 'plan_empleado_id');
    }
    
    public function edadContable(int $mes, int $anio): ?int
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        $fechaCorte = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return Carbon::parse($this->fecha_nacimiento)->diffInYears($fechaCorte);
    }

    public function sueldos()
    {
        return $this->hasMany(PlanSueldo::class, 'plan_empleado_id');
    }
    public function ultimoSueldo()
    {
        return $this->hasOne(PlanSueldo::class, 'plan_empleado_id')->latestOfMany('fecha_inicio');
    }
    /**
     * Obtiene el monto del sueldo vigente para un mes y año específicos.
     *
     * @param int $mes (1-12)
     * @param int $anio (Ej: 2024)
     * @return float|null
     */
    public function sueldo($mes, $anio): ?float
    {
        // 1. Crear el primer y último día del mes consultado
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->endOfDay();

        // 2. Buscar el registro vigente en ese rango
        $registro = $this->sueldos()
            ->where('fecha_inicio', '<=', $finMes)
            ->where(function ($query) use ($inicioMes) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicioMes);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        // 3. Retornar el monto en float si existe, o null
        return $registro ? (float) $registro->sueldo : null;
    }
    public function contrato(int $mes, int $anio): ?PlanContrato
    {
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        return $this->hasOne(PlanContrato::class, 'plan_empleado_id')
            ->where('fecha_inicio', '<=', $finMes)
            ->where(function ($query) use ($inicioMes) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicioMes);
            })
            ->orderByDesc('fecha_inicio')
            ->first();
    }
    public function ultimoContrato()
    {
        return $this->hasOne(PlanContrato::class, 'plan_empleado_id')->latestOfMany('fecha_inicio');
    }




    public function getNombreCompletoAttribute()
    {
        return "{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}";
    }

    public function getTipoPlanillaDescripcionAttribute()
    {
        $descripcion = '-';
        $ultimoContrato = $this->ultimoContrato;
        switch ($ultimoContrato?->tipo_planilla) {
            case 'agraria':
                $descripcion = 'P. AGRARIA';
                break;
            case 'oficina':
                $descripcion = 'P. OFICINA';
                break;
            default:
                $descripcion = 'P. DESCONOCIDA';
                break;
        }
        return $descripcion;
    }
    public function getColorGrupoAttribute(): ?string
    {
        return $this->ultimoContrato?->grupo?->color;
    }
    public function getColorTextoGrupoAttribute(): ?string
    {
        return $this->color_grupo ? '#000' : null;
    }

    public function cantidadHijosConAsignacionFamiliar(
        ?int $mes = null,
        ?int $anio = null
    ): int {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;

        // 1. Obtener vínculos activos de tipo 'hijo'
        $vinculosHijos = $this->derechoHabientes()
            ->where('activo', true)
            ->whereHas('derechoHabiente', function ($q) {
                $q->where('tipo', 'hijo');
            })
            ->with('derechoHabiente')
            ->get();

        // 2. Evaluar mediante el Servicio de Dominio
        $service = app(EmpleadoAsignacionFamiliarService::class);
        $resultado = $service->evaluarColeccion($vinculosHijos, $mes, $anio);

        // 3. Contar cuántos hijos califican
        return collect($resultado['detalle'])->where('califica', true)->count();
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? \Illuminate\Support\Str::uuid();
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->actualizado_por = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->eliminado_por = Auth::id();
                $model->saveQuietly(); // guarda sin volver a disparar eventos
            }
        });
    }
    /**
     * 1. Relación con todos los registros de cargos del empleado a través de sus contratos
     */
    public function empleadoCargos()
    {
        // Si PlanEmpleadoCargo se relaciona directamente con el empleado:
        return $this->hasMany(PlanEmpleadoCargo::class, 'plan_empleado_id');

        // NOTA: Si PlanEmpleadoCargo se conecta mediante PlanContrato, se usará hasManyThrough:
        // return $this->hasManyThrough(PlanEmpleadoCargo::class, PlanContrato::class, 'plan_empleado_id', 'plan_contrato_id');
    }

    /**
     * 2. Relación HasOne para el cargo VIGENTE (donde fecha_fin es null)
     */
    public function cargoActualRegistro(): HasOne
    {
        return $this->hasOne(PlanEmpleadoCargo::class, 'plan_empleado_id')
            ->whereNull('fecha_fin')
            ->latestOfMany('fecha_inicio'); // En caso de inconsistencias, toma el más reciente
    }

    /**
     * 3. Accessor para obtener directamente el objeto PlanCargo o el nombre del cargo
     * Uso: $empleado->cargo_actual (retorna el modelo PlanCargo)
     */
    public function cargoActual(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Retorna el Modelo PlanCargo asociado al registro activo
                return $this->cargoActualRegistro?->cargo;
            }
        );
    }

    /**
     * 4. Accessor alternativo para obtener solo el NOMBRE del cargo directamente
     * Uso: $empleado->nombre_cargo_actual (retorna string)
     */
    public function nombreCargoActual(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->cargoActualRegistro?->cargo?->nombre ?? 'Sin Cargo Asignado'
        );
    }
}
