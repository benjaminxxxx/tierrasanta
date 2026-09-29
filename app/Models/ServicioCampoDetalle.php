<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioCampoDetalle extends Model
{
    protected $table = 'servicios_campo_detalles';

    protected $fillable = [
        'servicio_campo_id',
        'fecha',
        'campo',
        'campania_id',
        'labor',
        'cantidad',
        'costo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'decimal:3',
        'costo' => 'decimal:2',
    ];

    public function servicio()
    {
        return $this->belongsTo(ServicioCampo::class, 'servicio_campo_id');
    }

    public function campoRelacion()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campania_id');
    }
}
