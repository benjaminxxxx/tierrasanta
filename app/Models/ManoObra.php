<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManoObra extends Model
{
    protected $table = 'mano_obras';    
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
    ];
}
