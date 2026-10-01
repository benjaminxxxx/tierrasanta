<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Carga de un macro de KARDEX anual (historial independiente: sin FKs a productos ni kardex). */
class KardexCarga extends Model
{
    protected $table = 'kardex_cargas';

    protected $fillable = [
        'archivo',
        'nombre_original',
        'anio',
        'tipo_kardex',
        'version_archivo',
        'subido_por',
        'subido_por_nombre',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(KardexCargaDetalle::class)->orderBy('fila');
    }
}
