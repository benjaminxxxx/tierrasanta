<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Documento de un contrato (sustento del cese: carta de renuncia, carta o acta de despido). Archivo en el disco local. */
class PlanContratoDocumento extends Model
{
    protected $table = 'plan_contrato_documentos';

    protected $fillable = [
        'plan_contrato_id', 'tipo', 'fecha_documento', 'nombre_original', 'ruta', 'mime', 'tamano', 'observacion', 'subido_por',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'tamano' => 'integer',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(PlanContrato::class, 'plan_contrato_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
