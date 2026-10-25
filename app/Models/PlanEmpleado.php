<?php

namespace App\Models;

use App\Services\Planilla\DerechoHabiente\EmpleadoAsignacionFamiliarService;
use App\Services\Planilla\Laboral\PlanillaLaboralConsulta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Empleado de planilla. Sus datos naturales (DNI, nombres, nacimiento, género, dirección…) viven en su persona y se
 * leen con los mismos nombres de siempre ($empleado->documento, ->nombres…). Para buscar u ordenar por ellos se usan
 * los scopes buscar(), porDocumento() y ordenarPor(). El historial laboral (contratos y partículas) se lee con
 * PlanillaLaboralConsulta.
 */
class PlanEmpleado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plan_empleados';

    protected $fillable = ['uuid', 'persona_id', 'orden', 'comentarios', 'creado_por', 'actualizado_por', 'eliminado_por'];

    protected $with = ['persona'];

    /** Datos de la persona que se exponen como atributos del empleado (también en toArray). */
    protected $appends = ['documento', 'nombres', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'genero', 'email', 'numero', 'direccion', 'nombre_completo'];

    /** Campo lógico del empleado => columna de personas (para buscar y ordenar). */
    public const CAMPOS_PERSONA = [
        'documento' => 'numero_documento', 'nombres' => 'nombres', 'apellido_paterno' => 'apellido_paterno', 'apellido_materno' => 'apellido_materno',
        'fecha_nacimiento' => 'fecha_nacimiento', 'genero' => 'genero', 'email' => 'email', 'numero' => 'telefono_movil', 'direccion' => 'direccion',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? Str::uuid();
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
                $model->saveQuietly();
            }
        });
    }

    // ------------------------------------------------------------------ relaciones

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    /** Usuario del sistema que es esta persona (si tiene). */
    public function usuario()
    {
        return $this->hasOne(User::class, 'persona_id', 'persona_id');
    }

    public function contratos()
    {
        return $this->hasMany(PlanContrato::class, 'plan_empleado_id');
    }

    public function pensiones()
    {
        return $this->hasMany(PlanEmpleadoPension::class, 'plan_empleado_id')->orderBy('desde');
    }

    public function pagos()
    {
        return $this->hasMany(PlanEmpleadoPago::class, 'plan_empleado_id')->orderBy('desde');
    }

    public function derechoHabientes()
    {
        return $this->hasMany(EmpleadoDerechoHabiente::class, 'empleado_id');
    }

    public function ultimoContrato()
    {
        return $this->hasOne(PlanContrato::class, 'plan_empleado_id')->latestOfMany('fecha_inicio');
    }

    /** Contrato vigente hoy: ya inició y no ha terminado. */
    public function contratoVigente()
    {
        $hoy = now()->toDateString();
        return $this->hasOne(PlanContrato::class, 'plan_empleado_id')->ofMany(
            ['fecha_inicio' => 'max'],
            fn($q) => $q->where('fecha_inicio', '<=', $hoy)->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy))
        );
    }

    // ------------------------------------------------------------------ datos de la persona

    public function getDocumentoAttribute(): ?string
    {
        return $this->persona?->numero_documento;
    }

    public function getNombresAttribute(): ?string
    {
        return $this->persona?->nombres;
    }

    public function getApellidoPaternoAttribute(): ?string
    {
        return $this->persona?->apellido_paterno;
    }

    public function getApellidoMaternoAttribute(): ?string
    {
        return $this->persona?->apellido_materno;
    }

    public function getFechaNacimientoAttribute(): ?string
    {
        return $this->persona?->fecha_nacimiento?->format('Y-m-d');
    }

    /** M / F (en personas se guarda masculino / femenino). */
    public function getGeneroAttribute(): ?string
    {
        return Persona::generoCorto($this->persona?->genero);
    }

    public function getEmailAttribute(): ?string
    {
        return $this->persona?->email;
    }

    public function getNumeroAttribute(): ?string
    {
        return $this->persona?->telefono_movil;
    }

    public function getDireccionAttribute(): ?string
    {
        return $this->persona?->direccion;
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}", ' ,');
    }

    /** Primer día contratado (del contrato más antiguo). */
    public function getFechaIngresoAttribute(): ?string
    {
        $c = $this->relationLoaded('contratos') ? $this->contratos->sortBy('fecha_inicio')->first() : $this->contratos()->orderBy('fecha_inicio')->first();
        return $c?->fecha_inicio?->format('Y-m-d');
    }

    // ------------------------------------------------------------------ scopes por datos de la persona

    public function scopePorDocumento($query, ?string $dni)
    {
        return $query->whereHas('persona', fn($q) => $q->where('numero_documento', $dni));
    }

    public function scopeBuscar($query, ?string $texto)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }
        return $query->whereHas('persona', fn($q) => $q->where(fn($q) => $q->where('nombres', 'like', "%{$texto}%")
            ->orWhere('apellido_paterno', 'like', "%{$texto}%")->orWhere('apellido_materno', 'like', "%{$texto}%")
            ->orWhere('numero_documento', 'like', "%{$texto}%")
            ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombres) LIKE ?", ["%{$texto}%"])));
    }

    /** M / F */
    public function scopeDeGenero($query, ?string $genero)
    {
        return $genero ? $query->whereHas('persona', fn($q) => $q->where('genero', Persona::generoLargo($genero))) : $query;
    }

    /** Ordena por un campo propio (orden) o de la persona (apellido_paterno, nombres, documento, genero…). */
    public function scopeOrdenarPor($query, string $campo, string $direccion = 'asc')
    {
        $direccion = strtolower($direccion) === 'desc' ? 'desc' : 'asc';
        if (isset(self::CAMPOS_PERSONA[$campo])) {
            return $query->orderBy(Persona::select(self::CAMPOS_PERSONA[$campo])->whereColumn('personas.id', 'plan_empleados.persona_id')->limit(1), $direccion);
        }
        return $query->orderBy($campo, $direccion);
    }

    /** Apellidos y nombres. */
    public function scopeAlfabetico($query)
    {
        return $query->ordenarPor('apellido_paterno')->ordenarPor('apellido_materno')->ordenarPor('nombres');
    }

    /** Empleados con un contrato vigente a la fecha (hoy por defecto). */
    public function scopeConContratoVigente($query, $fecha = null)
    {
        return $query->whereHas('contratos', fn($q) => $q->vigentes($fecha));
    }

    // ------------------------------------------------------------------ historial laboral (capa laboral)

    public function contrato(int $mes, int $anio): ?PlanContrato
    {
        return PlanillaLaboralConsulta::contratoDelMes($this, $mes, $anio);
    }

    public function contratoEn($fecha = null): ?PlanContrato
    {
        return PlanillaLaboralConsulta::contratoEn($this, $fecha);
    }

    /** Sueldo del mes (remuneración vigente del contrato del mes). */
    public function sueldo($mes, $anio): ?float
    {
        return PlanillaLaboralConsulta::sueldoDelMes($this, (int) $mes, (int) $anio);
    }

    public function pensionEn($fecha = null): ?PlanEmpleadoPension
    {
        return PlanillaLaboralConsulta::pensionEn($this, $fecha);
    }

    public function pagoEn($fecha = null): ?PlanEmpleadoPago
    {
        return PlanillaLaboralConsulta::pagoEn($this, $fecha);
    }

    /** Asignación (cargo y grupo) de hoy: la del contrato vigente o, si no tiene, la del último contrato. */
    public function getAsignacionActualAttribute(): ?PlanContratoAsignacion
    {
        $c = $this->contratoVigente ?? $this->ultimoContrato;
        return $c?->asignacionEn();
    }

    public function getCargoActualAttribute(): ?PlanCargo
    {
        return $this->asignacion_actual?->cargo;
    }

    public function getNombreCargoActualAttribute(): string
    {
        return $this->cargo_actual?->nombre ?? 'Sin Cargo Asignado';
    }

    public function getGrupoActualAttribute(): ?PlanGrupo
    {
        return $this->asignacion_actual?->grupo;
    }

    public function getColorGrupoAttribute(): ?string
    {
        return $this->grupo_actual?->color;
    }

    public function getColorTextoGrupoAttribute(): ?string
    {
        return $this->color_grupo ? '#000' : null;
    }

    public function getTipoPlanillaDescripcionAttribute(): string
    {
        return match ($this->ultimoContrato?->tipo_planilla) {
            'agraria' => 'P. AGRARIA',
            'oficina' => 'P. OFICINA',
            'externa' => 'EXTERNA',
            default => 'P. DESCONOCIDA',
        };
    }

    public function edadContable(int $mes, int $anio): ?int
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return (int) Carbon::parse($this->fecha_nacimiento)->diffInYears(Carbon::createFromDate($anio, $mes, 1)->endOfMonth());
    }

    /** Hijos (derecho habientes de tipo hijo, vínculo activo) que dan derecho a asignación familiar en el mes. */
    public function cantidadHijosConAsignacionFamiliar(?int $mes = null, ?int $anio = null): int
    {
        $mes ??= now()->month;
        $anio ??= now()->year;
        $vinculosHijos = $this->derechoHabientes()->where('activo', true)
            ->whereHas('derechoHabiente', fn($q) => $q->where('tipo', 'hijo'))
            ->with('derechoHabiente')->get();
        $resultado = app(EmpleadoAsignacionFamiliarService::class)->evaluarColeccion($vinculosHijos, $mes, $anio);
        return collect($resultado['detalle'])->where('califica', true)->count();
    }

    public function tieneHijosConAsignacionFamiliar(?int $mes = null, ?int $anio = null): bool
    {
        return $this->cantidadHijosConAsignacionFamiliar($mes, $anio) > 0;
    }
}
