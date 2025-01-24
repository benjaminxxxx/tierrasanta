<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaborValoracion extends Model
{
    protected $table="labor_valoracions";
    protected $fillable=['labor_id','kg_8','valor_kg_adicional','vigencia_desde'];
    public function getKgHoraAttribute(){
        return $this->kg_8/8;
    }
}
