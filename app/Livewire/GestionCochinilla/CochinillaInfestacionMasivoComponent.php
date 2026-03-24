<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\Auditoria;
use App\Models\CochinillaInfestacion;
use App\Services\Cochinilla\InfestacionServicio;
use App\Support\FormatoHelper;
use App\Traits\ListasComunes\HstListas;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaInfestacionMasivoComponent extends Component
{
    use LivewireAlert, ConSelectorMes, HstListas;
    public $breadcrumb = [];
    public array $filasModificadas = [];
    public $codigoActualizacion = '';
    public ?int $auditoriaModeloId = null;
    public array $auditoriaHistorial = [];
    public bool $modalAuditoria = false;
    public array $listaCampos = [];
    public function mount()
    {
        $this->breadcrumb = [
            ['route' => 'cochinilla.infestacion', 'label' => 'Infestaciones'],
            ['label' => 'Carga Masiva']
        ];
        $this->inicializarMesAnio();
        $this->listaCampos = $this->cargarListaHstCampos();
    }
    public function verAuditoria(int $id): void
    {
        $this->auditoriaModeloId = $id;
        $this->auditoriaHistorial = Auditoria::where('modelo', CochinillaInfestacion::class)
            ->where('modelo_id', $id)
            ->orderByDesc('fecha_accion')
            ->get()
            ->toArray();

        $this->modalAuditoria = true;
    }
    public function listarInfestaciones()
    {
        $mes = $this->mes;
        $anio = $this->anio;
        $data = CochinillaInfestacion::whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->with(['campoCampania'])
            ->get()
            ->map(function ($infestacion) {
                return array_merge($infestacion->toArray(), [
                    'campania' => $infestacion->campoCampania?->nombre_campania,
                    'madres_por_infestador' => round($infestacion->madres_por_infestador, 3),
                    'infestadores_por_ha' => round($infestacion->infestadores_por_ha, 2),
                ]);
            })
            ->toArray();

        $this->dispatch('cargarDataInfestacion', data: $data);
    }
    protected function despuesMesAnioModificado($anio, $mes)
    {
        $this->listarInfestaciones();
    }
    public function vincularConCampanias(): void
    {
        try {
            $sincronizados = InfestacionServicio::sincronizarCampaniasPorMes(
                (int) $this->mes,
                (int) $this->anio
            );

            $mensaje = $sincronizados > 0
                ? "{$sincronizados} infestaciones vinculadas con su campaña"
                : 'Todas las infestaciones ya tienen campaña asignada';

            $this->alert('success', $mensaje);
            $this->listarInfestaciones();

        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    // En Livewire, solo limpiar si todo fue bien
    public function guardarInfestacionMasivo(array $data)
    {
        try {
            $dataNormalizada = collect($data)->map(function ($fila) {
                return [
                    'id' => $fila['id'] ?? null,
                    'tipo_infestacion' => $fila['tipo_infestacion'] ?? null,
                    'fecha' => FormatoHelper::parseFecha($fila['fecha']),
                    'campo_nombre' => $fila['campo_nombre'] ?? null,
                    'area' => FormatoHelper::parseNumero((string) ($fila['area'] ?? '')),
                    'kg_madres' => FormatoHelper::parseNumero((string) ($fila['kg_madres'] ?? '')),
                    'campo_origen_nombre' => $fila['campo_origen_nombre'] ?? null,
                    'metodo' => $fila['metodo'] ?? null,
                    'numero_envases' => (float) ($fila['numero_envases'] ?? 0),
                    'capacidad_envase' => FormatoHelper::parseNumero((string) ($fila['capacidad_envase'] ?? '')),
                ];
            })->toArray();
            $resultado = InfestacionServicio::guardarInfestacionMasivo($dataNormalizada);
            $partes = [];
            if ($resultado['creados'] > 0)
                $partes[] = "{$resultado['creados']} creados";
            if ($resultado['actualizados'] > 0)
                $partes[] = "{$resultado['actualizados']} actualizados";
            if ($resultado['eliminados'] > 0)
                $partes[] = "{$resultado['eliminados']} eliminados";

            $mensaje = count($partes) > 0
                ? implode(', ', $partes)
                : 'Sin cambios';

            $this->alert('success', $mensaje);
            //permite forzar la actualizacion del componente de resumen en caso se guarde
            $this->codigoActualizacion = Str::random(5);
            $this->listarInfestaciones();
            $this->filasModificadas = []; // solo aquí se limpia
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-cochinilla.cochinilla-infestacion-masivo-component');
    }
}
