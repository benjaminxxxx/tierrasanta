<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaFacturadaCochinilla extends Model
{
    protected $table = 'venta_facturada_cochinillas';

    protected $fillable = [
        'fecha',
        'campo_campania_id',
        'factura',
        'tipo_venta',
        'comprador',
        'lote',
        'kg',
        'procedencia',
        'precio_venta_dolares',
        'punto_acido_carminico',
        'factor_saco',
        'tipo_cambio',
        'campo_campania_id'
    ];
    protected $appends = [
        'acido_carminico',
        'sacos',
        'ingresos',
        'ingreso_contable_soles',
    ];

    public function campania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }


    public function getAcidoCarminicoAttribute()
    {
        if ($this->precio_venta_dolares && $this->punto_acido_carminico && $this->punto_acido_carminico != 0) {
            return $this->precio_venta_dolares / $this->punto_acido_carminico;
        }
        return null;
    }

    public function getSacosAttribute()
    {
        if ($this->kg && $this->factor_saco && $this->factor_saco != 0) {
            return $this->kg / $this->factor_saco;
        }
        return null;
    }

    public function getIngresosAttribute()
    {
        if ($this->kg && $this->precio_venta_dolares) {
            return $this->kg * $this->precio_venta_dolares;
        }
        return null;
    }

    public function getIngresoContableSolesAttribute()
    {
        if ($this->ingresos && $this->tipo_cambio) {
            return $this->ingresos * $this->tipo_cambio;
        }
        return null;
    }

}
