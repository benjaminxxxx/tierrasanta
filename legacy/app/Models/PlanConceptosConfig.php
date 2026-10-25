<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanConceptosConfig extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'plan_conceptos_configs';

    /**
     * Atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo_sunat',      // Código oficial de la planilla electrónica (ej. 0121, 0803)
        'nombre',            // Descripción del concepto (ej. Remuneración Básica)
        'abreviatura_excel', // Nombre corto para cabeceras de reportes
        'clase',             // ingreso, descuento, aporte_patronal
        'origen',            // blanco (legal), negro (interno)
        'metodo_calculo',    // porcentaje, monto_fijo, manual
        'valor_base',        // El valor numérico para el cálculo
        'incluye_igv',       // Flag para costos de empleador que llevan IGV (Vida Ley)
        'fecha_inicio',      // Inicio de vigencia de la regla
        'fecha_fin',         // Fin de vigencia (null si es actual)
        'activo',            // Estado lógico del concepto
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_base' => 'decimal:4',
        'incluye_igv' => 'boolean',
        'activo' => 'boolean',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
    ];
}