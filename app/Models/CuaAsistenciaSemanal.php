<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//antes CuadrillaAsistencia
class CuaAsistenciaSemanal extends Model
{
    use HasFactory;
    protected $table = "cua_asistencia_semanal";
    protected $fillable = ['titulo', 'fecha_inicio', 'fecha_fin', 'total', 'estado'];

    public function grupos()
    {
        return $this->hasMany(CuaAsistenciaSemanalGrupo::class, 'cua_asi_sem_id');
    }
    public function getActividadesAttribute()
    {
        $fechaInicio = $this->fecha_inicio;
        $fechaFin = $this->fecha_fin;
        return Actividad::whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }
    public static function buscarSemana($fecha)
    {
        $CuaAsistenciaSemanal = self::whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->first();

        if (!$CuaAsistenciaSemanal) {
            throw new \Exception("No hay una semana para esta fecha {$fecha}");
        } else {
            return $CuaAsistenciaSemanal;
        }
    }
    public function actualizarTotales()
    {
        $grupos = $this->grupos;
        $sumaGeneral = 0;
        $cuadrilleros = $this->cuadrillerosEnSemana();

        //actualizar total_costo en cuadrillero_actividades
        //considerar que las actividades son por dia

        $fechaInicio = Carbon::parse($this->fecha_inicio);
        $fechaFin = Carbon::parse($this->fecha_fin);

        $preciosPersonalizados = CuaAsistenciaSemanalGrupoPrecios::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get()
            ->groupBy(function ($item) {
                return $item->cua_asistencia_semanal_grupo_id . '_' . $item->fecha . '_' . $item->cua_asi_sem_cua_id;
            });

        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        foreach ($periodo as $fecha) {

            $actividades = Actividad::whereDate('fecha', $fecha)->get();
            $listaActividades = $actividades->pluck('id')->toArray();
            $listaActividadesHoras = $actividades->pluck('horas_trabajadas', 'id')->toArray();


            foreach ($cuadrilleros as $cuadrillero) {

                //Actividades en las que participo un cuadrillero en ese dia de la semana
                //ejemplo:: un cuadrilleroA estuvo en tres actividades, dos podas y una plantaion
                $actividadesDelCuadrillero = CuadrilleroActividad::whereIn('actividad_id', $listaActividades)
                    ->where('cua_asi_sem_cua_id', $cuadrillero['cua_asi_sem_cua_id'])
                    ->get();



                if ($actividadesDelCuadrillero->count() > 0) {

                    /**
                     * Digamos que de las 3 actividades 1 es sin opcion a bono y 2 con opciones a bonos
                     * La que no tiene bono tiene una duracion de 5 horas, 
                     * la actividad 2 con opcion a bono es de 8 horas, pero solo hizo la recogida 1, que dura 4 horas
                     * la actividad 3 con opcion a bono es de 8 horas, pero solo hizo la recogida 2, que dura 4 horas
                     * seria un total de 5 + 4 + 4, tiene 13 horas, eso esta correcto
                     */
                    $totalHorasTrabajadas = $actividadesDelCuadrillero->sum(function ($cuadrillero) {
                        $recogidas = $cuadrillero->recogidas;

                        if ($recogidas->count() == 0 && !$cuadrillero->actividad->valoracion) {
                            return $cuadrillero->actividad->horas_trabajadas;
                        } else {
                            return $recogidas->sum(function ($recogida) {
                                return $recogida->recogida->horas;
                            });
                        }
                    });

                    /**
                     * Aqui esta el codigo para obtener el costo por hora, el costo por hora es un calculo ya realizado por el modelo
                     * el costo hora sera el costo por dia del grupo / 8
                     * pero recordemos que hay costo por hora para un dia en especifico dentro del grupo
                     * tambien tenemos el costo personalizado por cuadrillero
                     */
                    $grupo = CuaAsistenciaSemanalGrupo::find($cuadrillero['grupo']);
                    if (!$grupo) {
                        throw new \Exception("El grupo no existe");
                    }
                    $fechaStr = $fecha->toDateString();
                    $costoHora = (float) $grupo->costo_hora;
                    $personalizadoKey = $grupo->id . '_' . $fechaStr . '_';
                    $personalizadoCuadrillero = $grupo->id . '_' . $fechaStr . '_' . $cuadrillero['cua_asi_sem_cua_id'];

                    if (isset($preciosPersonalizados[$personalizadoKey])) {
                        $personalizado = $preciosPersonalizados[$personalizadoKey]->first();
                        $costoHora = (float) $personalizado->costo_hora;
                    }
                    if (isset($preciosPersonalizados[$personalizadoCuadrillero])) {
                        $personalizado = $preciosPersonalizados[$personalizadoCuadrillero]->first();
                        $costoHora = (float) $personalizado->costo_hora;
                    }

                    $costoDia = $costoHora * $totalHorasTrabajadas;
                    $totalBono = $actividadesDelCuadrillero->sum('total_bono');

                    $cuadrillaHoras = CuadrillaHora::updateOrCreate(
                        [
                            'cua_asi_sem_cua_id' => $cuadrillero['cua_asi_sem_cua_id'],
                            'fecha' => $fecha->format('Y-m-d')
                        ],
                        [
                            'horas' => $totalHorasTrabajadas,
                            'costo_dia' => $costoDia,
                            'bono'=>$totalBono
                        ]
                    );

                    foreach ($actividadesDelCuadrillero as $actividadDelCuadrillero) {

                        if ($cuadrillaHoras) {

                            if ($totalHorasTrabajadas > 0) {

                                /**
                                 * En este caso si no tiene valoracion, el calculo es lo que dure toda la actividad
                                 * en caso tenga valoracion, es la suma de las horas que tiene registrado por actividad
                                 * ejemplo:
                                 * digamos que de las tres actividades una con valoracion tiene 2 recogidas, cada una de 4 horas
                                 * quiere decir que si hace  solo una recogida, seria 4 horas
                                 */
                                $recogidas = $actividadDelCuadrillero->recogidas;
                                $horasEnActividad = $listaActividadesHoras[$actividadDelCuadrillero->actividad_id];
                                if ($actividadDelCuadrillero->actividad->valoracion) {
                                    $horasEnActividad = $recogidas->sum(function ($recogida) {
                                        return $recogida->recogida->horas;
                                    });
                                }
                                $totalCostoActividad = $horasEnActividad * $costoHora;                               
                                $actividadDelCuadrillero->update([
                                    'total_costo' => $totalCostoActividad
                                ]);
                            }
                        }
                    }
                } else {

                    CuadrillaHora::where('cua_asi_sem_cua_id', $cuadrillero['cua_asi_sem_cua_id'])
                        ->where('fecha', $fecha->format('Y-m-d'))->delete();
                }
            }
        }
        //para obtener los totales debo considerar primero los siguientes niveles
        //cada grupo
        //cada cuadrillero
        //cada fecha
        //cada actividad

        foreach ($grupos as $grupo) {

            $costoGrupal = 0;
            $cuadrilleros = $grupo->cuadrillerosEnAsistencia;

            if ($cuadrilleros->count() == 0) {
                //No hay ningun cuadrillero en este grupo, su costo ha de ser 0 + Gastos agregados
                $grupo->update([
                    'total_costo' => 0
                ]);
            } else {
                foreach ($cuadrilleros as $cuadrillero) {
                    //cada cuadrillero tiene un gasto
                    $cuadrillaHoras = $cuadrillero->cuadrillaHoras->sum(function ($cuadrillaHora) {
                        return $cuadrillaHora->costo_dia + $cuadrillaHora->bono;
                    });

                    $cuadrillero->update([
                        'monto_recaudado' => $cuadrillaHoras
                    ]);
                    $costoGrupal += $cuadrillaHoras;
                }
                $sumaGeneral += $costoGrupal;
                $grupo->update([
                    'total_costo' => $costoGrupal
                ]);
            }
        }
        $this->update([
            'total' => $sumaGeneral
        ]);
    }
    public function cuadrillerosEnSemana()
    {
        $grupos = $this->grupos;

        $lista = [];
        foreach ($grupos as $grupo) {
            $cuadrilleros = $grupo->cuadrillerosEnAsistencia;
            if ($cuadrilleros) {
                foreach ($cuadrilleros as $cuadrillero) {
                    $data = $cuadrillero->cuadrillero;
                    $lista[] = [
                        'cua_asi_sem_cua_id' => $cuadrillero->id,
                        'id' => $data->id,
                        'grupo' => $grupo->id,
                        'grupo_nombre' => $grupo->grupo->nombre,
                        'tipo' => 'cuadrilla',
                        'dni' => $data->dni,
                        'nombres' => $data->nombres,
                    ];
                }
            }
        }
        return collect($lista)->sortBy(['grupo', 'nombres']);
    }
    public static function cuadrillerosEnFecha($fecha)
    {

        $CuaAsistenciaSemanal =  self::buscarSemana($fecha);
        $grupos = $CuaAsistenciaSemanal->grupos; //()->cuadrillerosEnAsistencia();

        if (!$grupos) {
            throw new \Exception("No hay ningún grupo en el registro semanal {$CuaAsistenciaSemanal->id}");
        }
        $lista = [];
        foreach ($grupos as $grupo) {
            $cuadrilleros = $grupo->cuadrillerosEnAsistencia;
            if ($cuadrilleros) {
                foreach ($cuadrilleros as $cuadrillero) {
                    $data = $cuadrillero->cuadrillero;
                    $lista[] = [
                        'cua_asi_sem_cua_id' => $cuadrillero->id,
                        'id' => $data->id,
                        'grupo' => $grupo->id,
                        'grupo_nombre' => $grupo->grupo->nombre,
                        'tipo' => 'cuadrilla',
                        'dni' => $data->dni,
                        'nombres' => $data->nombres,
                    ];
                }
            }
        }
        return collect($lista)->sortBy(['grupo', 'nombres']);
    }
}
