<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Siembra de una campaña (grupo de hileras sembrado en su propia fecha) con sus etapas (ver CampaniaSectorProceso). */
class CampaniaSector extends Model
{
    protected $table = 'campania_sectores';

    protected $fillable = ['campo_campania_id', 'nombre', 'fecha_siembra', 'siembra_id', 'hilera_desde', 'hilera_hasta', 'origen'];

    protected $casts = ['hilera_desde' => 'integer', 'hilera_hasta' => 'integer', 'fecha_siembra' => 'date'];

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }

    /** Registro de siembras (misma fuente que Campo → Siembras) */
    public function siembra(): BelongsTo
    {
        return $this->belongsTo(Siembra::class, 'siembra_id');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(CampaniaSectorEtapa::class, 'campania_sector_id')->orderBy('fecha_desde');
    }
}
