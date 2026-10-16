<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ticket de un error no controlado (ver App\Services\Sistema\Error\SistemaErrorCrud). */
class ErrorSistema extends Model
{
    protected $table = 'errores_sistema';

    public const ESTADOS = ['pendiente' => 'Pendiente', 'revisado' => 'Revisado', 'resuelto' => 'Resuelto'];

    protected $fillable = [
        'huella', 'clase', 'mensaje', 'archivo', 'linea', 'traza', 'url', 'ruta', 'metodo_http', 'componente',
        'accion', 'user_id', 'ip', 'navegador', 'estado', 'nota', 'revisado_por', 'revisado_en',
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
