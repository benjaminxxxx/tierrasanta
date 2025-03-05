<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostoMensual extends Model {
    use HasFactory;

    protected $table = 'costos_mensuales';

    protected $fillable = [
        'anio', 'mes',

        // Costos fijos
        'fijo_administrativo_blanco', 'fijo_administrativo_negro',
        'fijo_financiero_blanco', 'fijo_financiero_negro',
        'fijo_gastos_oficina_blanco', 'fijo_gastos_oficina_negro',
        'fijo_depreciaciones_blanco', 'fijo_depreciaciones_negro',
        'fijo_costo_terreno_blanco', 'fijo_costo_terreno_negro',

        // Costos operativos
        'operativo_servicios_fundo_blanco', 'operativo_servicios_fundo_negro',
        'operativo_mano_obra_indirecta_blanco', 'operativo_mano_obra_indirecta_negro',
    ];
}
