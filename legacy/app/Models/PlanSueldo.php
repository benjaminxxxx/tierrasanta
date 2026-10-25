<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class PlanSueldo extends Model
{
    protected $table = 'plan_sueldos';
    protected $fillable = [
        'plan_empleado_id',
        'sueldo',
        'fecha_inicio',
        'fecha_fin',
        'creado_por'
    ];
    public function creador(){
        return $this->belongsTo(User::class,'creado_por');
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
        });
    }
}
