<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteDiario extends Model
{
    use HasFactory;
    protected $table = 'plan_registros_diarios';
    // Definir los campos que se pueden asignar de forma masiva
    protected $fillable = [
        'documento',
        'empleado_nombre',
        'asistencia',
        'tipo_trabajador',
        'total_horas',
        'fecha',
        'orden',
        'bono_productividad'
    ];

    public function detalles()
    {
        return $this->hasMany(ReporteDiarioDetalle::class);
    }
    public function actividadesBonos()
    {
        return $this->hasMany(PlanActividadBono::class, 'registro_diario_id');
    }
    public static function empleadosEnFecha($fecha, $campo, $labor)
    {
        return self::whereDate('fecha', $fecha)
            ->whereHas('detalles', function ($q) use ($campo, $labor) {
                $q->where('campo', $campo);
                $q->where('labor', $labor);
            })
            ->get()
            ->map(function ($empleados) {
                $empleado = Empleado::where('documento', $empleados->documento)->first();
                if (!$empleado) {
                    throw new \Exception("El empleado ya no existe.");
                }

                return [
                    'id' => $empleado->id,
                    'tipo' => 'planilla',
                    'dni' => $empleados->documento,
                    'nombres' => $empleados->empleado_nombre,
                ];
            })->toArray();
    }

}
