<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Arqueo: lo que realmente hay en cada fuente en una fecha. Su total se compara con el disponible. */
class CajaArqueo extends Model
{
    protected $table = 'caja_arqueos';

    protected $fillable = ['fecha', 'observacion', 'creado_por'];

    protected $casts = ['fecha' => 'date'];

    public function detalles()
    {
        return $this->hasMany(CajaArqueoDetalle::class, 'caja_arqueo_id');
    }
}
