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
    public function ingresos()
    {
        return $this->belongsToMany(CochinillaIngreso::class, 'cochinilla_ingreso_infestacion')
            ->withPivot('kg_asignados')
            ->withTimestamps();
    }
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
    #region Alias
    public function getMadresPorInfestadorAliasAttribute($value)
    {
        $madres = $this->madres_por_infestador;

        if (!is_numeric($madres)) {
            return '0gr.'; // o podrías devolver 'N/A' según tu lógica
        }

        return number_format($madres * 10000, 0) . 'gr.';
    }
    public function getInfestadoresPorHaAliasAttribute($value)
    {
        $infestadores_por_ha = $this->infestadores_por_ha;

        if (!is_numeric($infestadores_por_ha)) {
            return '0 infest.'; // o podrías devolver 'N/A' según tu lógica
        }

        return number_format($infestadores_por_ha, 0) . ' infest';
    }
    #endregion
}
