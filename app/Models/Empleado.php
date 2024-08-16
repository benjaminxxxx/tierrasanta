<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'cargo',
        'salario',
        'fecha_nacimiento',
        'direccion',
    ];
    
}
