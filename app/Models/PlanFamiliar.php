<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanFamiliar extends Model
{
    use HasFactory;
    protected $table = 'plan_familiares';

    protected $fillable = [
        'plan_empleado_id',
        'nombres',
        'fecha_nacimiento',
        'documento',
        'creado_por',
        'actualizado_por',
        'esta_estudiando',
    ];

    public function empleado()
    {
        return $this->belongsTo(PlanEmpleado::class, 'plan_empleado_id');
    }
    public function getEdadAttribute()
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }
    public function getEstaEstudiandoStringAttribute()
    {
        return $this->esta_estudiando==1?'Si':'No';
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->actualizado_por = Auth::id();
            }
        });
    }
}
