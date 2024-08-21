<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Empleado extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'documento',
        'fecha_ingreso',
        'comentarios',
        'status',
        'email',
        'numero',
        'cargo_id',
        'genero',
        'descuento_sp_id',
        'salario',
        'fecha_nacimiento',
        'direccion',
        'grupo_codigo',
        'compensacion_vacacional',
        'esta_jubilado'
    ];
    public function descuento(){
        return $this->belongsTo(DescuentoSP::class,'descuento_sp_id');
    }
    public function cargo(){
        return $this->belongsTo(Cargo::class,'cargo_id');
    }
    public function grupo(){
        return $this->belongsTo(Grupo::class,'grupo_codigo');
    }
    public function getNombreCompletoAttribute()
    {
        return "{$this->apellido_paterno} {$this->apellido_materno}, {$this->nombres}";
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
}
