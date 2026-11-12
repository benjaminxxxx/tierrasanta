<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Grupo de hileras de una campaña (hileras desde–hasta) con sus etapas (ver CampaniaSectorProceso). */
class CampaniaSector extends Model
{
    protected $table = 'campania_sectores';

    protected $fillable = ['campo_campania_id', 'nombre', 'hilera_desde', 'hilera_hasta', 'origen'];

    protected $casts = ['hilera_desde' => 'integer', 'hilera_hasta' => 'integer'];

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(CampaniaSectorEtapa::class, 'campania_sector_id')->orderBy('fecha_desde');
    }
}
