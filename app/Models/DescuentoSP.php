<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescuentoSP extends Model
{
    use HasFactory;
    protected $table = 'descuento_sp';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'codigo',
        'descripcion',
        'porcentaje',
        'porcentaje_65',
        'tipo'
    ];
}
