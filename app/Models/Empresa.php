<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $primaryKey = 'ruc'; // Definimos 'ruc' como la clave primaria

    public $incrementing = false; // No incrementará automáticamente el campo RUC, ya que es un valor fijo

    protected $fillable = [
        'ruc', 'razon_social', 'establecimiento'
    ];

    public $timestamps = false;
}
