<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class DerechoHabiente extends Model
{
    protected $fillable = [
        'nombres',
        'fecha_nacimiento',
        'tipo_documento',
        'documento',
        'tipo',
        'discapacidad_severa',
        'fecha_inicio_estudios',
        'fecha_fin_estudios',
        'percibe_pension_no_contributiva',
        'creado_por',
        'actualizado_por',
    ];
    protected $casts = [
    'fecha_nacimiento' => 'date',
    'discapacidad_severa' => 'boolean',
    'percibe_pension_no_contributiva' => 'boolean',
    'fecha_inicio_estudios' => 'date',
    'fecha_fin_estudios' => 'date',
];

    protected function edad(): Attribute
    {
        return Attribute::get(fn() => $this->fecha_nacimiento?->age);
    }

    public function vinculos()
    {
        return $this->hasMany(EmpleadoDerechoHabiente::class);
    }
}
