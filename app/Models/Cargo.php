<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;
    protected $fillable = ['codigo','nombre'];
    protected $table = 'cargos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

}
