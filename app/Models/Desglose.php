<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desglose extends Model
{
    use HasFactory;

    protected $table = 'desgloses';

    protected $fillable = [
        'codigo_vale',
        'fecha',
        'monto_inicial',
        'saldo_anterior',
        'monto_total_gastos',
        'saldo_final',
        'entregado_por',
        'recibido_por',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_inicial' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'monto_total_gastos' => 'decimal:2',
        'saldo_final' => 'decimal:2',
    ];

    /**
     * Un desglose tiene muchos detalles de egresos/pagos.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DesgloseDetalle::class, 'desglose_id');
    }
}