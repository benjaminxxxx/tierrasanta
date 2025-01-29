<?php

namespace App\Services;

use App\Exports\CampoConsumoExport;
use App\Models\Actividad;
use App\Models\AlmacenProductoSalida;
use App\Models\CampoCampania;
use App\Models\CamposCampaniasConsumo;
use App\Models\CategoriaProducto;
use App\Models\ResumenConsumoProductos;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class CampaniaServicio
{
    public $campoCampaniaId;
    public $campoCampania;
    public function __construct($campoCampaniaId = null)
    {
        $this->campoCampaniaId = $campoCampaniaId;
        if ($this->campoCampaniaId) {
            $this->campoCampania = CampoCampania::find($this->campoCampaniaId);
            if (!$this->campoCampania) {
                throw new Exception("La campaña no existe.");
            }
        }
    }
    /**
     * Actualiza los Gastos y Consumos de una determinada campaña
     * @param int $campoCampaniaId
     */
    public function actualizarGastosyConsumos()
    {
        $this->campoCampania->update([
            'gasto_planilla' => $this->gastoPlanilla(),
            'gasto_cuadrilla' => $this->gastoCuadrilla()
        ]);

        $this->actualizarConsumo();
    }
    public function actualizarConsumo()
    {
        $this->campoCampania->resumenConsumoProductos()->delete();
        $this->campoCampania->camposCampaniasConsumo()->delete();
        $fecha_inicio = $this->campoCampania->fecha_inicio;
        $fecha_fin = $this->campoCampania->fecha_fin;
        $campo = $this->campoCampania->campo;

        $query = AlmacenProductoSalida::whereDate('fecha_reporte', '>=', $fecha_inicio);
        if ($fecha_fin) {
            $query->whereDate('fecha_reporte', '<=', $fecha_fin);
        }
        $registros = $query->where('campo_nombre', $campo)->get();
        if ($registros) {

            $resumenConsumoProductosData = [];
            foreach ($registros as $registro) {

                $resumenConsumoProductosData[] = [
                    'fecha' => $registro->fecha_reporte,
                    'campo' => $registro->campo_nombre,
                    'producto' => $registro->producto->nombre_completo,
                    'categoria' => $registro->producto->categoria->nombre,
                    'categoria_id' => $registro->producto->categoria_id,
                    'cantidad' => $registro->cantidad,
                    'total_costo' => $registro->total_costo,
                    'campos_campanias_id' => $this->campoCampania->id
                ];
            }

            ResumenConsumoProductos::insert($resumenConsumoProductosData);

            $categoriaProductos = CategoriaProducto::all();
            if ($categoriaProductos) {
                $camposCampaniasConsumo = [];
                foreach ($categoriaProductos as $categoriaProducto) {
                    $totalConsumido = ResumenConsumoProductos::where('campos_campanias_id', $this->campoCampania->id)
                        ->where('categoria_id', $categoriaProducto->id)
                        ->sum('total_costo');
                    $data = [
                        'campos_campanias_id' => $this->campoCampania->id,
                        'categoria_id' => $categoriaProducto->id,
                    ];
                    $filePath = 'consumo_reportes/' . date('Y-m') . Str::slug('/REPORTE_CONSUMO_' . mb_strtoupper($this->campoCampania->nombre_campania) . '_'. mb_strtoupper($categoriaProducto->nombre).'_'. $this->campoCampania->campo) . '.xlsx';
                    
                    Excel::store(new CampoConsumoExport($data), $filePath, 'public');
                    $camposCampaniasConsumo[] = [
                        'campos_campanias_id' => $this->campoCampania->id,
                        'categoria_id' => $categoriaProducto->id,
                        'monto' => $totalConsumido,
                        'reporte_file' => $filePath
                    ];
                }
                CamposCampaniasConsumo::insert($camposCampaniasConsumo);
            }
        }
    }
    public function gastoPlanilla()
    {
        return PlanillaServicio::calcularGastoPlanilla($this->campoCampaniaId);
    }
    public function gastoCuadrilla()
    {
        $query = Actividad::whereDate('fecha', '>=', $this->campoCampania->fecha_inicio);
        if ($this->campoCampania->fecha_fin) {
            $query->whereDate('fecha', '<=', $this->campoCampania->fecha_fin);
        }
        $actividades = $query->where('campo', $this->campoCampania->campo)->get();

        return $actividades->sum(function ($actividad) {
            return $actividad->cuadrillero_actividades->sum(function ($cuadrilleroActividad) {
                return $cuadrilleroActividad->total_bono + $cuadrilleroActividad->total_costo;
            });
        });
    }
}
