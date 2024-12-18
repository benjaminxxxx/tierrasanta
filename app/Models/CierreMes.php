<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreMes extends Model
{
    protected $table = 'cierre_mes';

    protected $fillable = [
        'anio', 'mes', 'estado', 'fecha_cierre', 'creado_por', 'actualizado_por'
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
