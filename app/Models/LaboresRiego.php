<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboresRiego extends Model
{
    use HasFactory;

    protected $table = 'reg_labores';

    protected $fillable = [
        'nombre_labor',
    ];
}
