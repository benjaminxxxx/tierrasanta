<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;
    protected $table = 'configuracion';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';


    // Los atributos que se pueden asignar masivamente.
    protected $fillable = ['codigo', 'valor', 'descripcion'];
}
