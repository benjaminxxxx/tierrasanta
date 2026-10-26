<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RptDistribucionCombustible extends Model
{
    use HasFactory;

    protected $fillable = [
        'mes',
        'anio',
        'file_negro',
        'file_blanco',
        'total_combustible',
        'total_costo',
        'total_horas',
    ];
}
