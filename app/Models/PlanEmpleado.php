<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
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

    public function contratos()
    {
        return $this->hasMany(PlanContrato::class, 'plan_empleado_id');
    }
    
    public function ultimoContrato()
    {
        return $this->hasOne(PlanContrato::class, 'plan_empleado_id')->latestOfMany('fecha_inicio');
    }
   
    public function asignacionFamiliar()
    {
        return $this->hasMany(AsignacionFamiliar::class, 'empleado_id');
    }
    public function getNombreCompletoAttribute()
    {
        return "{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}";
    }

    public function getTipoPlanillaDescripcionAttribute()
    {
        $descripcion = '-';
        switch ($this->tipo_planilla) {
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

    public function getTieneAsignacionFamiliarAttribute()
    {
        // Obtener todas las asignaciones familiares del empleado
        $asignaciones = AsignacionFamiliar::where('empleado_id', $this->id)->get();

        $cantidadHijos = 0;


        foreach ($asignaciones as $asignacion) {
            // Calcular la edad del hijo
            $edad = Carbon::parse($asignacion->fecha_nacimiento)->age;

            // Verificar las condiciones
            if ($edad < 18 || ($edad >= 18 && $asignacion->esta_estudiando)) {
                $cantidadHijos++;
            }
        }

        // Determinar el mensaje basado en la cantidad de hijos
        $mensaje = $cantidadHijos === 0 ? "No" : ($cantidadHijos === 1 ? "1 Hijo" : "{$cantidadHijos} Hijos");

        // Retornar el array con el mensaje y la cantidad
        return [
            'mensaje' => $mensaje,
            'cantidad' => $cantidadHijos,
        ];
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
    
}
