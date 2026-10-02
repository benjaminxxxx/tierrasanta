<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tipo de cambio del día (hoja "Tipo de Cambio"): convierte el gasto a dólares. */
class CajaTipoCambio extends Model
{
    protected $table = 'caja_tipos_cambio';
    protected $primaryKey = 'fecha';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['fecha', 'valor'];

    protected $casts = ['valor' => 'decimal:4'];
}
