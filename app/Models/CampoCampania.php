<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampoCampania extends Model
{
    use HasFactory;

    protected $fillable = [
        'lote',
        'area',
        'campania',
        'fecha_vigencia'
    ];

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }
}
