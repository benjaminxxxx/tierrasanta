<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CochinillaInfestacion extends Model
{
    use HasFactory;

    protected $table = 'cochinilla_infestaciones';

    protected $fillable = [
        'tipo_infestacion',
        'fecha',
        'campo_nombre',
        'area',
        'campo_campania_id',
        'kg_madres',
        'kg_madres_por_ha',
        'campo_origen_nombre',
        'metodo',
        'numero_envases',
        'capacidad_envase',
        'infestadores',
        'madres_por_infestador',
        'infestadores_por_ha',
    ];

    // Relaciones

    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }

    public function campoOrigen()
    {
        return $this->belongsTo(Campo::class, 'campo_origen_nombre', 'nombre');
    }

    public function campoCampania()
    {
        return $this->belongsTo(CampoCampania::class, 'campo_campania_id');
    }
}
