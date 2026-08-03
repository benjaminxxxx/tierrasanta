<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\Configuracion;
use App\Services\Planilla\PlanillaServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaAsistenciaServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AsistenciaMensualComponent extends Component
{
    use ConSelectorMes;
    public $empleados = [];
    public $dias = [];
    const CODIGO_CONFIG_ORDEN = 'orden_planilla_asistencia';
    public $ordenGuardado = [];
    public $mostrandoModalOrden = false;
    public function mount()
    {
        $this->inicializarMesAnio();
        $this->ordenGuardado = $this->obtenerOrdenGuardado();
        $this->cargarDatos();
    }
    public function recalcularPagoPlanilla(){
        try {
            app(PlanillaServicio::class)->calcularGastosMensuales($this->mes,$this->anio);
            $this->cargarDatos();
        } catch (\Throwable $th) {
            $this->alert($th->getMessage());
        }
    }
    protected function despuesMesAnioModificado(string $mes, string $anio)
    {
        $this->cargarDatos();
    }
    protected function obtenerOrdenGuardado(): array
    {
        $config = Configuracion::where('codigo', self::CODIGO_CONFIG_ORDEN)->first();

        if (!$config || !$config->valor) {
            return [];
        }

        $decodificado = json_decode($config->valor, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    public function guardarOrdenConfiguracion($orden)
    {
        $ordenLimpio = collect($orden)
            ->filter(fn($item) => !empty($item['campo']))
            ->map(fn($item) => [
                'campo' => $item['campo'],
                'direccion' => $item['direccion'] === 'desc' ? 'desc' : 'asc',
            ])
            ->values()
            ->toArray();

        Configuracion::updateOrCreate(
            ['codigo' => self::CODIGO_CONFIG_ORDEN],
            [
                'valor' => json_encode($ordenLimpio),
                'descripcion' => 'Orden de visualización de la planilla de asistencia mensual',
            ]
        );

        $this->ordenGuardado = $ordenLimpio;
        $this->mostrandoModalOrden = false;
        $this->cargarDatos();
    }
    public function cargarDatos()
    {
        $mes = (int) $this->mes;
        $anio = (int) $this->anio;

        $this->dias = $this->obtenerDiasDelMesConTitulo($anio, $mes);

        $planilla = app(PlanillaEmpleadoServicio::class)
            ->obtenerPlanillaAgraria($mes, $anio, $this->ordenGuardado);

        $mapaAsistencia = app(PlanillaAsistenciaServicio::class)->obtenerMapaAsistenciaMensual($mes, $anio);
        $codigosFiltros = app(PlanillaAsistenciaServicio::class)->obtenerCodigosFiltros();

        $this->empleados = $planilla->map(function ($empleado) use ($mapaAsistencia, $codigosFiltros) {
            $contrato = $empleado->contratos->first();
            $infoAsistencia = $mapaAsistencia[$empleado->id] ?? null;
            $diasRegistrados = $infoAsistencia['dias'] ?? [];
           
            $diasAsistencia = [];
            $codigosDelMes = [];

            foreach ($this->dias as $dia) {
                $registro = $diasRegistrados[$dia['indice']] ?? null;
              
                $diasAsistencia[$dia['indice']] = [
                    'horas' => $registro['horas'] ?? null,
                    'sueldo' => $registro['costo_dia'] ?? null,
                    'color' => $registro['color'] ?? ($dia['es_domingo'] ? '#FFC000' : null),
                    'tipo' => $registro['tipo'] ?? null,
                    'descripcion' => $registro['descripcion'] ?? null,
                ];

                if ($registro['tipo'] ?? null) {
                    $codigosDelMes[] = $registro['tipo'];
                }
            }
            
                
            return [
                'id' => $empleado->id,
                'grupo' => $contrato?->grupo?->codigo,
                'nombre_completo' => mb_strtoupper($empleado->nombre_completo ?? ''),
                'documento' => $empleado->documento ?? null,
                'cargo' => $contrato->cargo ?? null,
                'dias' => $diasAsistencia,
                'total_horas' => $infoAsistencia['total_horas'] ?? 0,
                'sueldo_real_liquidado' => $infoAsistencia['sueldo_real_liquidado'] ?? 0,
                'tiene_permiso' => (bool) array_intersect($codigosDelMes, $codigosFiltros['permiso']),
                'tiene_descanso_medico' => (bool) array_intersect($codigosDelMes, $codigosFiltros['descanso_medico']),
                'tiene_vacaciones' => (bool) array_intersect($codigosDelMes, $codigosFiltros['vacaciones']),
            ];
        })->values()->toArray();
        $this->dispatch('empleados-actualizados', empleados: $this->empleados, dias: $this->dias);
    }
    protected function obtenerDiasDelMesConTitulo(int $anio, int $mes): array
    {
        $diasConTitulo = [];
        $ultimoDiaMes = Carbon::createFromDate($anio, $mes)->endOfMonth()->day;
        $diasTitulo = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

        for ($dia = 1; $dia <= $ultimoDiaMes; $dia++) {
            $fecha = Carbon::createFromDate($anio, $mes, $dia);
            $diaSemana = (int) $fecha->format('N');

            $diasConTitulo[] = [
                'titulo' => $diasTitulo[$diaSemana - 1],
                'indice' => $dia,
                'es_domingo' => $diaSemana === 7,
            ];
        }

        return $diasConTitulo;
    }
    public function render()
    {
        return view('livewire.gestion-planilla.asistencia-mensual-component', [
            'camposOrdenables' => PlanillaEmpleadoServicio::camposOrdenables(),
        ]);
    }
}
