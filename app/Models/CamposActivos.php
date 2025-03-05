<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CamposActivos extends Model
{
    use HasFactory;

    protected $table = 'campos_activos';

    protected $fillable = [
        'campo_nombre',
        'mes',
        'anio',
    ];

    public $timestamps = true;

    /**
     * Relación con la tabla de campos.
     */
    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo_nombre', 'nombre');
    }
}
