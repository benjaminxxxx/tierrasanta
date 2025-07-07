<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labores extends Model
{
    use HasFactory;
    protected $table = "labores";
    
    protected $fillable = [
        'nombre_labor',
        'bono',
        'estado',
        'codigo',
        'estandar_produccion',
        'unidades',
        'tramos_bonificacion',
    ];
    public function valoraciones(){
        return $this->hasMany(LaborValoracion::class,'labor_id');
    }
    public function getTieneBonoAttribute(){
        return $this->bono=='1'?'Activado':'Desactivado';   
    }
    public function getValoracionActualAttribute(){
        $valoracionActual = $this->valoraciones()->orderBy('vigencia_desde','desc')->first();
        if($valoracionActual){
            return $valoracionActual->kg_8 . 'kg en 8 horas, valor adicional x hora: ' . $valoracionActual->valor_kg_adicional;
        }

        return 'Sin valoración';   
    }
    
}
