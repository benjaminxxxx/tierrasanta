<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanCargo extends Model
{
    use HasFactory;
    protected $fillable = ['codigo','nombre'];
    protected $table = 'plan_cargos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

}
