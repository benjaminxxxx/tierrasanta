<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;
    protected $fillable = ['codigo','descripcion'];
    protected $table = 'grupos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
}
