<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionFamiliar extends Model
{
    use HasFactory;
    protected $table = 'asignacion_familiar';

    protected $fillable = [
        'nombres',
        'fecha_nacimiento',
        'documento',
        'empleado_id',
        'esta_estudiando',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
    public function getEdadAttribute()
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }
    public function getEstaEstudiandoStringAttribute()
    {
        return $this->esta_estudiando==1?'Si':'No';
    }
}
