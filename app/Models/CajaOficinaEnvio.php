<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Envío de cambios de la caja de oficina a la caja de movimientos. Pendiente hasta que se anexa. */
class CajaOficinaEnvio extends Model
{
    public const PENDIENTE = 'pendiente';
    public const ANEXADO = 'anexado';

    protected $table = 'caja_oficina_envios';

    protected $fillable = ['estado', 'nota', 'cambios', 'enviado_por', 'enviado_nombre', 'anexado_por', 'anexado_nombre', 'anexado_at'];

    protected $casts = ['anexado_at' => 'datetime'];

    public function detalles()
    {
        return $this->hasMany(CajaOficinaEnvioDetalle::class, 'caja_oficina_envio_id')->orderBy('id');
    }
}
