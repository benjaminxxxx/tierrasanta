<?php

namespace App\Livewire\Cochinilla;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use App\Services\Cochinilla\CochinillaIngresoServicio;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

//MODULO COCHINILLA INGRESO - WIZARD (Lote + Sublotes)
class CochinillaIngresoDetalleComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;

    public $mostrarFormulario = false;
    public $step = 1; // 1: buscar lote, 2: datos del lote, 3: sublotes (handsontable)

    // Paso 1
    public $loteBuscado;
    /** Al editar: n° de lote corregido (error de digitación) */
    public $loteNuevo;

    // Contexto cargado / en edición
    public $cochinillaIngreso; // instancia de CochinillaIngreso o null si es nuevo
    public $esNuevo = false;
    /** Se abrió un ingreso ya registrado: cualquier cambio pide motivo y queda en auditoría */
    public bool $editandoExistente = false;
    public string $motivoEdicion = '';

    // Paso 2 (datos del lote)
    public $campoSeleccionado;
    public $area;
    public $campania; // instancia de CampoCampania
    public $fecha;
    public $observacionSeleccionada;

    // Paso 3 (handsontable)
    public $idTable;
    public $observaciones; // catálogo completo (paso 2)
    public $observacionesCodigos; // solo códigos (dropdown handsontable)
    public $campos = [];

    protected $listeners = [
        'agregarIngreso',
        'editarIngreso',
        'storeTableDataCochinillaIngreso',
    ];

    public function mount()
    {
        $this->idTable = 'table_' . Str::random(10);
        $this->campos = Campo::listar();
        $this->observaciones = CochinillaObservacion::all();
        $this->observacionesCodigos = $this->observaciones->pluck('codigo')->toArray();
    }

    /* ==========================
     *  APERTURA DEL WIZARD
     * ========================== */

    public function agregarIngreso()
    {
        $this->resetWizard();
        $this->loteBuscado = CochinillaIngresoServicio::generarCodigoSiguiente();
        $this->mostrarFormulario = true;
    }

    /** $paso 2 abre directo los datos del lote (n°, campo, fecha) */
    public function editarIngreso($lote, $paso = null)
    {
        $this->resetWizard();
        $this->loteBuscado = $lote;
        $this->mostrarFormulario = true;
        $this->buscarLote();
        if ((int) $paso === 2 && $this->cochinillaIngreso) {
            $this->step = 2;
        }
    }

    public function resetWizard()
    {
        $this->resetErrorBag();
        $this->reset([
            'step',
            'loteBuscado',
            'loteNuevo',
            'cochinillaIngreso',
            'esNuevo',
            'editandoExistente',
            'motivoEdicion',
            'campoSeleccionado',
            'area',
            'campania',
            'fecha',
            'observacionSeleccionada',
        ]);
        $this->step = 1;
    }

    /* ==========================
     *  PASO 1: BUSCAR LOTE
     * ========================== */

    public function buscarLote()
    {
        $this->validate([
            'loteBuscado' => 'required|integer|min:1',
        ]);

        $ingreso = CochinillaIngreso::where('lote', $this->loteBuscado)->first();

        if ($ingreso) {
            $this->cargarIngresoExistente($ingreso);
            $this->step = 3;
        } else {
            $this->prepararNuevoIngreso();
            $this->step = 2;
        }
    }

    protected function cargarIngresoExistente(CochinillaIngreso $ingreso)
    {
        $this->cochinillaIngreso = $ingreso;
        $this->esNuevo = false;
        $this->editandoExistente = true;

        $this->loteNuevo = $ingreso->lote;
        $this->campoSeleccionado = $ingreso->campo;
        $this->area = $ingreso->area;
        $this->campania = $ingreso->campoCampania;
        $this->fecha = $ingreso->fecha;
        $this->observacionSeleccionada = $ingreso->observacion;

        $this->dispatch('cargarData', $ingreso->detalles->toArray());
    }

    protected function prepararNuevoIngreso()
    {
        $this->cochinillaIngreso = null;
        $this->esNuevo = true;

        $this->campoSeleccionado = null;
        $this->area = null;
        $this->campania = null;
        $this->fecha = now()->format('Y-m-d');
        $this->observacionSeleccionada = null;
    }

    /* ==========================
     *  PASO 2: DATOS DEL LOTE
     * ========================== */

    public function updatedCampoSeleccionado($valorNuevoCampo)
    {
        $campo = Campo::where('nombre', $valorNuevoCampo)->first();
        $this->area = $campo?->area;
        $this->cargarCampania();
    }

    public function updatedFecha()
    {
        $this->cargarCampania();
    }

    protected function cargarCampania()
    {
        if ($this->campoSeleccionado && $this->fecha) {
            $this->campania = CampoCampania::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        } else {
            $this->campania = null;
        }
    }

    public function guardarPaso2()
    {
        $this->validate([
            'campoSeleccionado' => 'required|exists:campos,nombre',
            'fecha' => 'required|date',
            'observacionSeleccionada' => 'required',
        ]);

        if (!$this->campania) {
            return $this->errorAlert('No hay campañas disponibles para este campo y fecha.');
        }
        if (!$this->motivoValido()) {
            return;
        }
        $antes = $this->cochinillaIngreso?->only(self::CAMPOS_AUDITADOS);

        // Lote ya registrado: se actualiza tal cual (puede cambiar su n° si se digitó mal)
        if ($this->editandoExistente && $this->cochinillaIngreso) {
            $this->validate(['loteNuevo' => 'required|integer|min:1'], [], ['loteNuevo' => 'N° de lote']);
            try {
                $movidos = DB::transaction(function () {
                    $ingreso = $this->cochinillaIngreso->fresh();
                    $movidos = app(\App\Services\Cochinilla\Lote\CochinillaLoteProceso::class)->cambiarNumero($ingreso, (int) $this->loteNuevo);
                    $ingreso->update([
                        'campo' => $this->campoSeleccionado,
                        'area' => $this->area,
                        'campo_campania_id' => $this->campania->id,
                        'observacion' => $this->observacionSeleccionada,
                        'fecha' => $this->fecha,
                    ]);
                    $this->cochinillaIngreso = $ingreso->fresh();
                    return $movidos;
                });
            } catch (\Throwable $e) {
                return $this->errorAlert($e);
            }
            $this->loteBuscado = $this->cochinillaIngreso->lote;
            $this->auditar($antes, $this->cochinillaIngreso->only(self::CAMPOS_AUDITADOS), 'Datos');
            if ((int) $antes['lote'] !== (int) $this->cochinillaIngreso->lote) {
                $adoptados = $movidos['venteados_adoptados'] + $movidos['filtrados_adoptados'];
                $this->alert('success', "Lote {$antes['lote']} cambiado a {$this->cochinillaIngreso->lote}: pasaron sus sublotes, "
                    . "{$movidos['venteados']} venteado(s) y {$movidos['filtrados']} filtrado(s)"
                    . ($adoptados ? " y se unieron {$adoptados} registro(s) que estaban sin ingreso con el n° {$this->cochinillaIngreso->lote}." : '.'));
            }
            $this->dispatch('detalleIngresoAgregado');
            $this->dispatch('cargarData', $this->cochinillaIngreso->detalles->toArray());
            $this->step = 3;
            return;
        }

        try {
            $this->cochinillaIngreso = CochinillaIngreso::updateOrCreate(
                ['lote' => $this->loteBuscado],
                [
                    'campo' => $this->campoSeleccionado,
                    'area' => $this->area,
                    'campo_campania_id' => $this->campania->id,
                    'observacion' => $this->observacionSeleccionada,
                    'fecha' => $this->fecha,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return $this->errorAlert('Ese número de lote ya fue registrado por otra persona justo ahora. Vuelve al paso 1 e intenta con otro número.');
            }
            throw $e;
        }

        $this->auditar($antes, $this->cochinillaIngreso->only(self::CAMPOS_AUDITADOS), 'Datos');
        $this->esNuevo = false;
        $this->dispatch('cargarData', $this->cochinillaIngreso->detalles->toArray());
        $this->step = 3;
    }

    public function volverAPaso2()
    {
        $this->step = 2;
    }

    /* ==========================
     *  PASO 3: SUBLOTES (HANDSONTABLE)
     * ========================== */

    public function storeTableDataCochinillaIngreso($datos)
    {
        if (!$this->cochinillaIngreso) {
            return;
        }
        if (!$this->motivoValido()) {
            return;
        }
        $sublotesAntes = $this->sublotesParaAuditoria();

        DB::beginTransaction();

        try {
            $this->cochinillaIngreso->detalles()->delete();

            $indice = 0;
            $data = [];
            $ultimaFecha = null;
            $ultimaObservacion = null;

            foreach ($datos as $value) {
                if (empty($value['fecha']) || empty($value['total_kilos']) || empty($value['observacion'])) {
                    continue;
                }

                $fecha = FormatoHelper::parseFecha($value['fecha']);
                if (!$fecha) {
                    continue;
                }

                $indice++;
                $subloteCodigo = $this->cochinillaIngreso->lote . '.' . $indice;
                $ultimaFecha = $fecha;
                $ultimaObservacion = $value['observacion'];

                $data[] = [
                    'cochinilla_ingreso_id' => $this->cochinillaIngreso->id,
                    'sublote_codigo' => $subloteCodigo,
                    'fecha' => $fecha,
                    'total_kilos' => $value['total_kilos'],
                    'observacion' => $value['observacion'],
                ];
            }

            if (!empty($data)) {
                CochinillaIngresoDetalle::insert($data);
            }

            $total = CochinillaIngresoDetalle::where('cochinilla_ingreso_id', $this->cochinillaIngreso->id)
                ->sum('total_kilos');

            $actualizacion = ['total_kilos' => $total];
            if ($ultimaFecha) {
                $actualizacion['fecha'] = $ultimaFecha;
                $this->fecha = $ultimaFecha;
            }
            if ($ultimaObservacion) {
                $actualizacion['observacion'] = $ultimaObservacion;
                $this->observacionSeleccionada = $ultimaObservacion;
            }

            $this->cochinillaIngreso->update($actualizacion);
            $this->cochinillaIngreso->refresh();
            $this->auditar(['sublotes' => $sublotesAntes], ['sublotes' => $this->sublotesParaAuditoria()], 'Sublotes');

            $this->alert('success', 'Registro exitoso');
            $this->dispatch('detalleIngresoAgregado');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errorAlert($e);
        }
    }

    /* ==========================
     *  TRAZABILIDAD (quién y por qué)
     * ========================== */

    /** Campos del lote que se comparan en la auditoría al editar. */
    private const CAMPOS_AUDITADOS = ['lote', 'campo', 'area', 'campo_campania_id', 'observacion', 'fecha'];

    /** Al editar un ingreso ya registrado el motivo es obligatorio. */
    private function motivoValido(): bool
    {
        if (!$this->editandoExistente || mb_strlen(trim($this->motivoEdicion)) >= 5) {
            return true;
        }
        $this->addError('motivoEdicion', 'Indica por qué editas este ingreso (mínimo 5 letras).');
        $this->errorAlert('Este ingreso ya estaba registrado: escribe el motivo de la edición antes de guardar.');
        return false;
    }

    private function sublotesParaAuditoria(): array
    {
        return $this->cochinillaIngreso->detalles()->orderBy('sublote_codigo')->get(['sublote_codigo', 'fecha', 'total_kilos', 'observacion'])
            ->map(fn($d) => "{$d->sublote_codigo} · " . \Illuminate\Support\Carbon::parse($d->fecha)->format('d/m/Y') . " · {$d->total_kilos} kg · {$d->observacion}")
            ->all();
    }

    /** Registra en auditoría: alta del ingreso, o la edición con su motivo. */
    private function auditar(?array $antes, array $despues, string $que): void
    {
        $lote = $this->cochinillaIngreso->lote;
        if (!$this->editandoExistente) {
            if ($antes === null) {
                \App\Services\Reporte\AuditoriaServicio::registrar(CochinillaIngreso::class, $this->cochinillaIngreso->id, 'crear', null, $despues, "Registro del lote {$lote}");
            }
            return;
        }
        \App\Services\Reporte\AuditoriaServicio::registrar(CochinillaIngreso::class, $this->cochinillaIngreso->id, 'editar', $antes, $despues,
            mb_substr("{$que} del lote {$lote}. Motivo: " . trim($this->motivoEdicion), 0, 255));
    }


    public function render()
    {
        $origenInfestador = null;
        if ($this->step === 3 && $this->cochinillaIngreso
            && \App\Services\Cochinilla\Origen\CochinillaOrigenProceso::esDeInfestador($this->cochinillaIngreso->observacion)) {
            $this->cochinillaIngreso->refresh()->load('creadoPor:id,name', 'editadoPor:id,name');
            $origenInfestador = ['campania' => \App\Models\CampoCampania::find($this->cochinillaIngreso->campania_origen_id)?->nombre_campania];
        }
        return view('livewire.cochinilla.cochinilla-ingreso-detalle-component', [
            'origenInfestador' => $origenInfestador,
            'duenaPaso2' => $this->duenaPaso2(),
        ]);
    }

    /** Al registrar un infestador: a qué campaña pertenecerá (la que dio la mamá), antes de guardar. */
    private function duenaPaso2(): ?array
    {
        if ($this->step !== 2 || !$this->campoSeleccionado || !$this->fecha
            || !\App\Services\Cochinilla\Origen\CochinillaOrigenProceso::esDeInfestador($this->observacionSeleccionada)) {
            return null;
        }
        $datos = app(\App\Services\Cochinilla\Origen\CochinillaOrigenProceso::class)->resolver((new CochinillaIngreso())->forceFill([
            'campo' => $this->campoSeleccionado,
            'fecha' => $this->fecha,
            'observacion' => $this->observacionSeleccionada,
            'campo_campania_id' => $this->campania?->id,
        ]));
        return [
            'campania' => CampoCampania::find($datos['campania_origen_id']),
            'sin_envio' => !in_array($datos['origen_estado'], ['infestacion', 'cosecha'], true),
            'detalle' => $datos['origen_detalle'],
        ];
    }
}