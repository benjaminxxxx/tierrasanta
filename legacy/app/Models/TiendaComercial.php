<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiendaComercial extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'tienda_comercials';

    protected $fillable = [
        'ruc',
        'contacto',
        'razon_social',
        'nombre_comercial',
        'tipo_contribuyente',
        'condicion',
        'estado_contribuyente',
        'estado_domicilio',
        'fecha_inscripcion',
        'fecha_inicio_actividades',
        'direccion_fiscal',
        'distrito',
        'provincia',
        'departamento',
        'ciiu',
        'actividad_comercio_exterior',
        'verificado',
        'verificado_at',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];

    protected $casts = [
        'verificado' => 'boolean',
        'verificado_at' => 'datetime',
        'fecha_inscripcion' => 'date',
        'fecha_inicio_actividades' => 'date',
    ];
    public function getNombreAttribute()
    {
        return $this->razon_social ?? $this->nombre_comercial ?? $this->ruc;
    }
    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    // Relación con Compras
    public function compras()
    {
        return $this->hasMany(CompraProducto::class, 'tienda_comercial_id');
    }
}
