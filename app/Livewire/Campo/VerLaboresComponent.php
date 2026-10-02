<?php

namespace App\Livewire\Campo;

use App\Models\Labores;
use App\Models\ManoObra;
use App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas;
use Livewire\Component;

/**
 * Catálogo de labores para consultar códigos mientras se llena el registro diario: buscador, agrupado
 * por mano de obra y con las labores de suspensión (DM, V, FR…) primero.
 */
class VerLaboresComponent extends Component
{
    public $mostrarFormularioLabores = false;
    /** @var array<int, array{clave:string, nombre:string, color:?string, suspension:bool, labores:array}> */
    public $grupos = [];
    protected $listeners = ['verLabores'];

    public function render()
    {
        return view('livewire.campo.ver-labores-component');
    }

    public function verLabores()
    {
        $this->grupos = $this->armarGrupos();
        $this->mostrarFormularioLabores = true;
    }

    private function armarGrupos(): array
    {
        $manoObras = ManoObra::pluck('descripcion', 'codigo');
        $orden = array_flip(CampaniaCostoProduccionReglas::ORDEN_MANO_OBRA);

        $labores = Labores::with('tipoAsistencia:codigo,descripcion,color')->orderBy('codigo')->get();

        $grupos = [];
        foreach ($labores as $l) {
            $esSuspension = filled($l->tipo_asistencia_codigo);
            $clave = $esSuspension ? '_suspension' : ($l->codigo_mano_obra ?: '_sin');
            $grupos[$clave] ??= [
                'clave' => $clave,
                'nombre' => match ($clave) {
                    '_suspension' => 'No laborado / suspensiones (van a FDM)',
                    '_sin' => 'Sin mano de obra asignada',
                    default => $manoObras[$clave] ?? $clave,
                },
                'suspension' => $esSuspension,
                'labores' => [],
            ];
            $tramos = is_string($l->tramos_bonificacion) ? json_decode($l->tramos_bonificacion, true) : $l->tramos_bonificacion;
            $grupos[$clave]['labores'][] = [
                'codigo' => (string) $l->codigo,
                'nombre' => $l->nombre_labor,
                'asistencia' => $l->tipo_asistencia_codigo,
                'asistencia_nombre' => $l->tipoAsistencia?->descripcion,
                'color' => $l->tipoAsistencia?->color,
                'estandar' => $l->estandar_produccion ? trim($l->estandar_produccion . ' ' . $l->unidades) : null,
                'bono' => !empty($tramos),
                'paga_con_jornal' => (bool) $l->se_paga_con_jornal,
            ];
        }

        uasort($grupos, function ($a, $b) use ($orden) {
            $pa = $a['clave'] === '_suspension' ? -1 : ($a['clave'] === '_sin' ? 999 : ($orden[$a['clave']] ?? 900));
            $pb = $b['clave'] === '_suspension' ? -1 : ($b['clave'] === '_sin' ? 999 : ($orden[$b['clave']] ?? 900));
            return [$pa, $a['nombre']] <=> [$pb, $b['nombre']];
        });

        return array_values($grupos);
    }
}
