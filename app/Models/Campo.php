<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Campo extends Model
{
    protected $table = 'campos';
    protected $primaryKey = 'nombre';  // Define 'nombre' como la clave primaria
    public $incrementing = false;       // Desactiva el auto-incremento ya que 'nombre' es un string
    protected $keyType = 'string';      // Define el tipo de clave primaria como string

    protected $fillable = [
        'nombre',  // Nombre único del campo, será la clave primaria
        'alias',
        'campo_parent_nombre',
        'grupo',   // Grupo al que pertenece el campo
        'orden',   // Orden en el grupo
        'estado',  // Estado actual (e.g., regando, sin regar)
        'area',    // Área del campo en metros cuadrados
        'pos_x',   // Posición X en un canvas o div
        'pos_y',   // Posición Y en un canvas o div
    ];
    public static function listar()
    {
        return self::orderBy('orden')->get();
    }
    public function hijos()
    {
        return $this->hasMany(Campo::class, 'campo_parent_nombre');
    }

    // Método para verificar si el campo tiene hijos
    public function hasChildren()
    {
        return $this->hijos()->exists();
    }

    public function seEstaRegando()
    {
        $horaActual = Carbon::now()->format('H:i'); // Hora actual en formato HH:MM

        // Verifica si hay algún detalle de riego que esté en curso
        return $this->detalleRiegos()
            ->where('fecha', Carbon::now()->format('Y-m-d')) // Filtra por la fecha actual
            ->where('hora_inicio', '<=', $horaActual) // Hora de inicio debe ser menor o igual a la hora actual
            ->where('hora_fin', '>=', $horaActual) // Hora de fin debe ser mayor o igual a la hora actual
            ->exists(); 
    }

    // Accesor para verificar si el campo se regó hoy
    public function seRegoEnFecha($fecha)
    {
        $fecha = $fecha ? Carbon::parse($fecha) : Carbon::now();

        // Obtener el primer detalle de riego que coincide con la fecha
        $detalleRiego = $this->detalleRiegos()
        ->where('fecha', $fecha)
        ->get(); // Usamos first() en lugar de exists() para obtener el primer resultado

        if ($detalleRiego && $detalleRiego->count()>0) {
            $data = [];
            foreach ($detalleRiego as $riego) {
                $data[] = [
                    'regadorDocumento' => $riego->documento,
                    'hora_inicio' => Carbon::parse($riego->hora_inicio)->format('H:i'), // Convertir a HH:MM
                    'hora_fin' => Carbon::parse($riego->hora_fin)->format('H:i'), // Convertir a HH:MM
                    'nombreRegador'=>$riego->regador
                ];
            }
            return [
                'result' => true,
                'message' => 'El campo se regó en la fecha especificada.',
                'riegos' => $data
            ];
        } else {
            // Si no existe un registro, devolver un array indicando que no se regó
            return [
                'result' => false,
                'message' => 'El campo no se regó en la fecha especificada.',
                'regadorDocumento' => null,
                'riegos' => []
            ];
        }
    }
    public function detalleRiegos()
    {
        return $this->hasMany(ReporteDiarioRiego::class, 'campo', 'nombre');
    }

    public function campanias()
    {
        return $this->hasMany(CampoCampania::class, 'campo');
    }
    public function getCampaniaActualAttribute()
    {
        $ultimaCampania = self::campanias()->orderBy('fecha_inicio','desc')->first();
        return $ultimaCampania;
    }
}
