<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampoCampania extends Model
{
    use HasFactory;
    protected $table = 'campos_campanias';
    protected $fillable = [
        'nombre_campania',
        'campo',
        'gasto_fdm',
        'gasto_agua',
        'gasto_planilla',
        'gasto_cuadrilla',
        'fecha_inicio',
        'fecha_fin',
        'usuario_modificador',

        'gasto_planilla_file',
        'gasto_cuadrilla_file',
        'gasto_resumen_bdd_file'
    ];
    public function reporteCostoPlanilla()
    {
        return $this->hasMany(ReporteCostoPlanilla::class, 'campos_campanias_id');
    }
    public function camposCampaniasConsumo()
    {
        return $this->hasMany(CamposCampaniasConsumo::class, 'campos_campanias_id');
    }
    //consumos()
    public function resumenConsumoProductos()
    {
        return $this->hasMany(ResumenConsumoProductos::class, 'campos_campanias_id');
    }
    public function getListaConsumoAttribute()
    {
        $lista = [];
        $categorias = CategoriaProducto::all();
        if($categorias){
            foreach ($categorias as $categoria) {
                $consumo = self::camposCampaniasConsumo()->where('categoria_id',$categoria->id)->first();
            
                $lista[$categoria->id]=[
                    'categoria'=>$categoria->nombre,
                    'monto'=>$consumo?$consumo->monto:0,
                    'reporte_file'=>$consumo?$consumo->reporte_file:null,
                ];
            }
        }
        return $lista;
    }
    public function campo()
    {
        return $this->belongsTo(Campo::class, 'campo', 'nombre');
    }
    public function getFechaVigenciaAttribute(){
        $date = Carbon::parse($this->fecha_inicio);
        $date->locale('es');

        return $date->translatedFormat('j \d\e F \d\e\l\ Y');
    }
}
