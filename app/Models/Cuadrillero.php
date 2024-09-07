<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuadrillero extends Model
{
    use HasFactory;
    protected $table = 'cuadrilleros';

    protected $fillable = [
        'nombre_completo',
        'codigo_grupo',
        'dni',
        'codigo'
    ];
    public static function boot()
    {
        parent::boot();

        static::created(function ($cuadrillero) {
            $cuadrillero->codigo = 'CU' . str_pad($cuadrillero->id, 4, '0', STR_PAD_LEFT);
            $cuadrillero->save();
        });
    }
    public function grupoCuadrilla()
    {
        return $this->belongsTo(GruposCuadrilla::class, 'codigo_grupo', 'codigo');
    }
   
}
