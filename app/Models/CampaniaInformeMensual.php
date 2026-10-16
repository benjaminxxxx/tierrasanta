<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Informe mensual (foto congelada) de una campaña al cierre de un mes. */
class CampaniaInformeMensual extends Model
{
    protected $table = 'campania_informes_mensuales';

    protected $fillable = [
        'campo_campania_id',
        'anio',
        'mes',
        'fecha_desde',
        'fecha_corte',
        'es_cierre',
        'version',
        'datos',
        'advertencias',
        'total_soles',
        'total_usd_ha',
        'generado_por',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_corte' => 'date',
        'es_cierre' => 'boolean',
        'datos' => 'array',
        'advertencias' => 'array',
        'total_soles' => 'float',
        'total_usd_ha' => 'float',
    ];

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /**
     * Advertencias guardadas, sin la de "falta el informe del mes anterior" si ese informe ya se generó después.
     *
     * @return array<int, array{nivel: string, tipo: string, mensaje: string}>
     */
    public function advertenciasVigentes(): array
    {
        $avisos = $this->advertencias ?? [];
        if (!collect($avisos)->contains('tipo', 'secuencia')) {
            return $avisos;
        }
        $anterior = \Carbon\Carbon::create($this->anio, $this->mes, 1)->subMonthNoOverflow();
        $existe = self::where('campo_campania_id', $this->campo_campania_id)->where('anio', $anterior->year)->where('mes', $anterior->month)->exists();
        return $existe ? array_values(array_filter($avisos, fn($a) => $a['tipo'] !== 'secuencia')) : $avisos;
    }

    /** Clave del mes (AAAA-MM) para ordenar y comparar. */
    public function getPeriodoAttribute(): string
    {
        return sprintf('%04d-%02d', $this->anio, $this->mes);
    }
}
