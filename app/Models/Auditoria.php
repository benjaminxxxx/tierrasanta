<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public $timestamps = false; // usa fecha_accion propio

    protected $table = 'auditorias';

    protected $fillable = [
        'modelo',
        'modelo_id',
        'accion',
        'cambios',
        'observacion',
        'usuario_id',
        'usuario_nombre',
        'fecha_accion',
    ];

    protected $casts = [
        'cambios' => 'array',
        'fecha_accion' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
