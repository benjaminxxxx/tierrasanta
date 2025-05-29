<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaPesticida extends Model
{
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
    ];
}
