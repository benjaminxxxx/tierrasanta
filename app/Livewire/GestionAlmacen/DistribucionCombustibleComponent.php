<?php

namespace App\Livewire\GestionAlmacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use App\Models\Maquinaria;
use App\Services\Almacen\DistribucionCombustibleServicio;
use App\Traits\ListasComunes\HstListas;
use App\Traits\Selectores\ConSelectorMes;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DistribucionCombustibleComponent extends Component
{
    use HstListas, ConSelectorMes, LivewireAlert;
    public $listaCampos = [];
    public $listaMaquinarias = [];
    public $distribuciones = [];
    public array $filasModificadas = [];
    public ?string $filtroMaquinariaId = null;
    public ?string $filtroCampo = null;
    public function mount()
    {
        $this->inicializarMesAnio();
        $this->listaCampos = $this->cargarListaHstCampos();
        $this->listaMaquinarias = $this->cargarListaHstMaquinarias();
    }
    public function getMaquinarias($search)
    {
        $query = Maquinaria::orderBy('nombre');

        // Si hay búsqueda, filtrar
        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query
            ->limit(10)
            ->get(['id', 'nombre'])
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->nombre
            ])
            ->toArray();
    }
    protected function despuesMesAnioModificado($anio, $mes)
    {
        $this->generarDistribucion();
    }
    public function updatedFiltroMaquinariaId(): void
    {
        $this->generarDistribucion();
    }

    public function updatedFiltroCampo(): void
    {
        $this->generarDistribucion();
    }
    public function generarDistribucion(): void
    {
        $query = AlmacenProductoSalida::with(['distribuciones.maquinaria', 'maquinaria', 'producto'])
            ->whereMonth('fecha_reporte', $this->mes)
            ->whereYear('fecha_reporte', $this->anio)
            ->whereHas('producto', fn($q) => $q->where('categoria_codigo', 'combustible'))
            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''));

        if ($this->filtroMaquinariaId) {
            $query->where('maquinaria_id', $this->filtroMaquinariaId);
        }

        $salidas = $query->get();

        // Aplanar en array plano con flag es_salida
        $filas = [];

        $todasDistribuciones = DistribucionCombustible::whereIn(
            'almacen_producto_salida_id',
            $salidas->pluck('id')
        )->get()->groupBy('almacen_producto_salida_id');

        foreach ($salidas as $salida) {
            // Fila cabecera de salida — solo lectura
            $filas[] = [
                'id' => null, // las salidas no tienen id editable
                'salida_id' => $salida->id,
                'es_salida' => true,
                'fecha' => $salida->fecha_reporte,
                'hora_inicio' => null,
                'hora_fin' => null,
                'n_horas' => null,
                'campo_nombre' => null,
                'cant_combustible' => null,
                'costo_combustible' => null,
                'ingreso_salida' => $salida->cantidad,
                'labor_diaria' => null,
                'maquinaria_id' => $salida->maquinaria_id,
                'maquinaria_nombre' => $salida->maquinaria?->nombre,
                'precio' => $salida->costo_por_kg,
                'ratio' => null,
                'costo' => $salida->total_costo,
            ];

            $distribuciones = $todasDistribuciones[$salida->id] ?? collect();
            $totalHorasSalida = $distribuciones->sum(fn($d) => $d->horas);
            // Filas de distribuciones del grupo
            foreach ($distribuciones as $dist) {

                // Filtro por campo (opcional)
                if ($this->filtroCampo && $dist->campo !== $this->filtroCampo) {
                    continue;
                }

                // Calcular ratio localmente sin query adicional
                $ratio = $totalHorasSalida > 0 ? $dist->horas / $totalHorasSalida : 0;
                $cantCombustible = $salida->cantidad * $ratio;
                $costoMaquinaria = $cantCombustible * ($salida->costo_por_kg ?? 0);
                $valorCosto = $dist->horas > 0 ? $costoMaquinaria / $dist->horas : 0;

                $filas[] = [
                    'id' => $dist->id,
                    'salida_id' => $salida->id,
                    'es_salida' => false,
                    'fecha' => $dist->fecha,
                    'hora_inicio' => $dist->hora_inicio,
                    'hora_fin' => $dist->hora_salida,
                    'n_horas' => $dist->horas,
                    'campo_nombre' => $dist->campo,
                    'cant_combustible' => $cantCombustible,
                    'costo_combustible' => $costoMaquinaria,
                    'ingreso_salida' => null,
                    'labor_diaria' => $dist->actividad,
                    'maquinaria_id' => $dist->maquinaria_id,
                    'maquinaria_nombre' => $dist->maquinaria?->nombre,
                    'precio' => $salida->costo_por_kg,
                    'ratio' => $ratio,
                    'costo' => $valorCosto,
                ];
            }
        }

        $this->dispatch('actualizarDistribuciones', data: $filas);
    }
    public function guardarDistribuciones(array $data)
    {
        try {
            $resultados = DistribucionCombustibleServicio::guardarDistribuciones($data);

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->filasModificadas = [];
            $this->generarDistribucion();
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-almacen.distribucion-combustible-component');
    }
}