<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'persona_id',
        'tipo_contribuyente',
        'estado_contribuyente',
        'estado_domicilio',
        'condicion',
        'fecha_inscripcion',
        'fecha_inicio_actividades',
        'ciiu',
        'actividad_comercio_exterior',
        'es_agente_retencion',
        'es_buen_contribuyente',
        'verificado',
        'verificado_at',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
        'fecha_inicio_actividades' => 'date',
        'verificado' => 'boolean',
        'es_agente_retencion' => 'boolean',
        'es_buen_contribuyente' => 'boolean',
        'verificado_at' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /* =========================================================================
     * ACCESSORS (Atributos delegados de Persona)
     * ========================================================================= */

    protected function nombreMostrar(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->nombre_mostrar ?? "Proveedor #{$this->id}"
        );
    }

    protected function numeroDocumento(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->numero_documento ?? ''
        );
    }

    protected function tipoDocumento(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->tipo_documento ?? ''
        );
    }

    protected function direccion(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->direccion ?? ''
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->email ?? ''
        );
    }

    protected function telefono(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->telefono_movil ?? $this->persona?->telefono ?? ''
        );
    }

    protected function razonSocial(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->persona?->razon_social ?? ''
        );
    }
}