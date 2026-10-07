<?php

namespace App\Services\Campo\Labor;

use App\Models\Labores;
use App\Models\LaborVigencia;
use App\Services\Costos\Consolidacion\BddManoObraServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Reasignar un código de labor a otra labor desde una fecha: lo que significaba hasta el día anterior queda en
 * labor_vigencias y la labor del código pasa a ser la nueva. Los registros anteriores a la fecha siguen
 * leyéndose con la labor de entonces.
 *
 * La fecha debe ser posterior al último registro del código: si hubiera registros desde esa fecha, cambiarían de
 * labor sin que nadie los toque.
 */
class CampoLaborVigenciaProceso
{
    public function __construct(private CampoLaborVigenciaConsulta $consulta)
    {
    }

    /**
     * @param array{nombre_labor:string, codigo_mano_obra:string, unidades?:?string, estandar_produccion?:mixed,
     *              tipo_asistencia_codigo?:?string} $nueva
     */
    public function reasignar(int $laborId, string $desde, array $nueva, ?string $motivo = null): Labores
    {
        $labor = Labores::withTrashed()->findOrFail($laborId);
        $desde = Carbon::parse($desde)->startOfDay();
        $codigo = (int) $labor->codigo;

        foreach (['unidades', 'estandar_produccion', 'tipo_asistencia_codigo'] as $campo) {
            if (($nueva[$campo] ?? null) === '') {
                $nueva[$campo] = null;
            }
        }
        $v = Validator::make($nueva + ['desde' => $desde->toDateString()], [
            'nombre_labor' => 'required|string|max:255',
            'codigo_mano_obra' => 'required|exists:mano_obras,codigo',
            'unidades' => 'nullable|string|max:20',
            'estandar_produccion' => 'nullable|integer|min:0',
            'tipo_asistencia_codigo' => 'nullable|not_in:A|exists:plan_tipo_asistencias,codigo',
        ], [
            'nombre_labor.required' => 'Escribe el nombre de la nueva labor.',
            'codigo_mano_obra.required' => 'Elige la mano de obra de la nueva labor.',
        ]);
        $v->after(function ($v) use ($labor, $desde, $codigo, $nueva) {
            if ($labor->vigente_desde && $desde->lte($labor->vigente_desde)) {
                $v->errors()->add('desde', 'El código ya significa la labor actual desde el ' . $labor->vigente_desde->format('d/m/Y') . ': la nueva fecha debe ser posterior.');
            }
            $ultimo = $this->consulta->ultimosUsos([$codigo])[$codigo] ?? null;
            if ($ultimo && $desde->toDateString() <= $ultimo) {
                $v->errors()->add('desde', "El código {$codigo} tiene registros hasta el " . Carbon::parse($ultimo)->format('d/m/Y')
                    . ': la nueva labor debe empezar después, para no cambiar la labor de esos registros.');
            }
            if (mb_strtolower(trim($nueva['nombre_labor'] ?? '')) === mb_strtolower(trim($labor->nombre_labor))) {
                $v->errors()->add('nombre_labor', 'Es el mismo nombre: para corregir el nombre usa Editar; reasignar es para otra labor.');
            }
        });
        $v->validate();

        return DB::transaction(function () use ($labor, $desde, $nueva, $motivo, $codigo) {
            $antes = $labor->only(['codigo', 'nombre_labor', 'codigo_mano_obra', 'tipo_asistencia_codigo', 'unidades', 'vigente_desde']);

            LaborVigencia::create([
                'codigo' => $codigo,
                'nombre_labor' => $labor->nombre_labor,
                'codigo_mano_obra' => $labor->codigo_mano_obra,
                'tipo_asistencia_codigo' => $labor->tipo_asistencia_codigo,
                'unidades' => $labor->unidades,
                'desde' => $labor->vigente_desde,
                'hasta' => $desde->copy()->subDay()->toDateString(),
                'motivo' => $motivo ? mb_substr(trim($motivo), 0, 500) : null,
                'creado_por' => auth()->id(),
            ]);

            if ($labor->trashed()) {
                $labor->restore(); // un código desactivado vuelve a usarse con la nueva labor
            }
            $labor->update([
                'nombre_labor' => trim($nueva['nombre_labor']),
                'codigo_mano_obra' => $nueva['codigo_mano_obra'],
                'unidades' => $nueva['unidades'] ?? null,
                'estandar_produccion' => $nueva['estandar_produccion'] ?? null,
                'tipo_asistencia_codigo' => $nueva['tipo_asistencia_codigo'] ?? null,
                'tramos_bonificacion' => null, // los tramos eran de la labor anterior
                'vigente_desde' => $desde->toDateString(),
                'actualizado_por' => auth()->id(),
                'eliminado_por' => null,
            ]);

            AuditoriaServicio::registrar(Labores::class, $labor->id, 'editar', $antes,
                $labor->fresh()->only(array_keys($antes)), "Código {$codigo} reasignado desde el " . $desde->format('d/m/Y') . ($motivo ? ": {$motivo}" : ''));

            // Por si hubiera costos ya calculados desde esa fecha con el nombre anterior
            BddManoObraServicio::registrarCambio($desde->toDateString(), now()->toDateString());
            return $labor;
        });
    }
}
