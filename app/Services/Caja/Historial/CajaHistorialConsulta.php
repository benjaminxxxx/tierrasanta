<?php

namespace App\Services\Caja\Historial;

use App\Models\Auditoria;
use App\Models\CajaArqueo;
use App\Models\CajaCierre;
use App\Models\CajaCierreEvento;
use App\Models\CajaMovimiento;
use Illuminate\Support\Carbon;

/**
 * Historial de caja (solo lectura):
 * - Cierres y reaperturas de cada mes: quién, cuándo, con qué saldo, por qué y cuántas veces.
 * - Actividad: altas, ediciones (qué cambió) y eliminaciones (con motivo) de movimientos y arqueos,
 *   leídas de la auditoría general.
 */
class CajaHistorialConsulta
{
    public const MODELOS = [
        CajaMovimiento::class => 'Movimiento',
        CajaArqueo::class => 'Arqueo',
        CajaCierre::class => 'Cierre de mes',
        \App\Models\CajaOficinaMovimiento::class => 'Caja de oficina',
        \App\Models\CajaOficinaEnvio::class => 'Envío de oficina',
    ];

    /** Campos que no se muestran en "qué cambió" (internos o repetidos). */
    private const OCULTOS = ['id', 'created_at', 'updated_at', 'deleted_at', 'creado_por', 'actualizado_por', 'eliminado_por',
        'caja_clasificador_id', 'empresa', 'moneda', 'orden'];

    private const ETIQUETAS = [
        'numero_caja' => 'N° caja', 'es_contable' => 'Contable', 'condicion' => 'Condición', 'categoria' => 'Categoría',
        'codigo' => 'Código', 'beneficiario' => 'Beneficiario', 'descripcion' => 'Gastos B+N', 'clasificador_1' => 'Clasificador 1',
        'clasificador_2' => 'Clasificador 2', 'subgrupo_ng' => 'Sub-grupo NG', 'subgrupo_bl' => 'Sub-grupo BL', 'fecha' => 'Fecha',
        'semana' => 'Semana', 'tipo_documento' => 'T. Doc', 'numero_documento' => 'N° Doc', 'situacion_cheque' => 'Situación cheque',
        'importe_usd' => 'Importe $', 'tipo_cambio_operacion' => 'TC del pago', 'importe' => 'Importe S/', 'importe_detalle' => 'Operación',
        'tipo_cambio' => 'TC', 'color_fondo' => 'Color de fondo', 'color_texto' => 'Color de letra', 'negrita' => 'Negrita',
        'motivo_eliminacion' => 'Motivo', 'estado' => 'Estado', 'saldo_final' => 'Saldo final',
    ];

    /**
     * Meses del año con su estado y todos sus cierres/reaperturas.
     *
     * @return array<int, array{mes:int, nombre:string, estado:?string, cerrado_veces:int, reabierto_veces:int, eventos:array}>
     */
    public function cierres(int $anio): array
    {
        $estados = CajaCierre::where('anio', $anio)->pluck('estado', 'mes');
        $eventos = CajaCierreEvento::where('anio', $anio)->orderBy('created_at')->orderBy('id')->get()->groupBy('mes');
        $conMovimientos = CajaMovimiento::whereYear('fecha', $anio)->selectRaw('MONTH(fecha) as mes, COUNT(*) as n')
            ->groupBy('mes')->pluck('n', 'mes');

        $meses = [];
        foreach (range(1, 12) as $mes) {
            $delMes = $eventos->get($mes, collect());
            if (!$delMes->count() && !isset($conMovimientos[$mes])) {
                continue;
            }
            $meses[] = [
                'mes' => $mes,
                'nombre' => ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->translatedFormat('F')),
                'estado' => $estados[$mes] ?? null,
                'movimientos' => (int) ($conMovimientos[$mes] ?? 0),
                'cerrado_veces' => $delMes->where('accion', 'cerrado')->count(),
                'reabierto_veces' => $delMes->where('accion', 'reabierto')->count(),
                'eventos' => $delMes->map(fn(CajaCierreEvento $e) => [
                    'accion' => $e->accion,
                    'fecha' => $e->created_at?->format('d/m/Y H:i'),
                    'usuario' => $e->usuario_nombre ?? '—',
                    'saldo' => $e->saldo !== null ? (float) $e->saldo : null,
                    'movimientos' => $e->movimientos,
                    'motivo' => $e->motivo,
                ])->values()->all(),
            ];
        }
        return $meses;
    }

    /**
     * Actividad de caja (auditoría), más reciente primero.
     *
     * @param array{desde?:?string, hasta?:?string, tipo?:?string, accion?:?string, usuario?:?string, buscar?:?string} $f
     */
    public function actividad(array $f, int $porPagina = 25)
    {
        $paginado = Auditoria::whereIn('modelo', array_keys(self::MODELOS))
            ->where('modelo_id', '>', 0) // la importación se registra con id 0
            ->when($f['desde'] ?? null, fn($q, $d) => $q->where('fecha_accion', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['hasta'] ?? null, fn($q, $h) => $q->where('fecha_accion', '<=', Carbon::parse($h)->endOfDay()))
            ->when($f['tipo'] ?? null, fn($q, $t) => $q->where('modelo', $t))
            ->when($f['accion'] ?? null, fn($q, $a) => $q->where('accion', $a))
            ->when($f['usuario'] ?? null, fn($q, $u) => $q->where('usuario_id', $u))
            ->when($f['buscar'] ?? null, fn($q, $b) => $q->where(fn($w) => $w->where('cambios', 'like', "%{$b}%")->orWhere('observacion', 'like', "%{$b}%")))
            ->orderByDesc('fecha_accion')->orderByDesc('id')
            ->paginate($porPagina);

        $movimientos = CajaMovimiento::withTrashed()
            ->whereIn('id', $paginado->getCollection()->where('modelo', CajaMovimiento::class)->pluck('modelo_id'))
            ->get()->keyBy('id');

        $paginado->setCollection($paginado->getCollection()->map(function (Auditoria $a) use ($movimientos) {
            $cambios = is_string($a->cambios) ? json_decode($a->cambios, true) : ($a->cambios ?? []);
            return [
                'id' => $a->id,
                'fecha' => Carbon::parse($a->fecha_accion)->format('d/m/Y H:i:s'),
                'usuario' => $a->usuario_nombre ?? 'Sistema',
                'tipo' => self::MODELOS[$a->modelo] ?? class_basename($a->modelo),
                'accion' => $a->accion,
                'registro' => $this->describir($a, $cambios, $movimientos->get($a->modelo_id)),
                'observacion' => $a->observacion,
                'cambios' => $a->accion === 'editar' ? $this->diferencias($cambios) : [],
            ];
        }));

        return $paginado;
    }

    /**
     * Historial de un movimiento: alta, ediciones y eliminación.
     *
     * @return array{movimiento: array, eventos: array}
     */
    public function deMovimiento(int $id): array
    {
        $m = CajaMovimiento::withTrashed()->with(['creadoPor:id,name', 'actualizadoPor:id,name', 'eliminadoPor:id,name'])->findOrFail($id);
        $eventos = Auditoria::where('modelo', CajaMovimiento::class)->where('modelo_id', $id)
            ->orderBy('fecha_accion')->orderBy('id')->get()
            ->map(function (Auditoria $a) {
                $cambios = is_string($a->cambios) ? json_decode($a->cambios, true) : ($a->cambios ?? []);
                return [
                    'fecha' => Carbon::parse($a->fecha_accion)->format('d/m/Y H:i:s'),
                    'usuario' => $a->usuario_nombre ?? 'Sistema',
                    'accion' => $a->accion,
                    'observacion' => $a->observacion,
                    'cambios' => $a->accion === 'editar' ? $this->diferencias($cambios) : [],
                ];
            })->all();

        return [
            'movimiento' => [
                'resumen' => $this->resumenMovimiento($m),
                'creado' => $m->created_at?->format('d/m/Y H:i') . ' · ' . ($m->creadoPor?->name ?? 'Importación desde Excel'),
                'editado' => $m->actualizado_por ? $m->updated_at?->format('d/m/Y H:i') . ' · ' . $m->actualizadoPor?->name : null,
                'eliminado' => $m->trashed() ? $m->deleted_at?->format('d/m/Y H:i') . ' · ' . ($m->eliminadoPor?->name ?? '—') : null,
                'motivo_eliminacion' => $m->motivo_eliminacion,
            ],
            'eventos' => $eventos,
        ];
    }

    /** Usuarios que aparecen en la auditoría de caja (para el filtro). */
    public function usuarios(): array
    {
        return Auditoria::whereIn('modelo', array_keys(self::MODELOS))->whereNotNull('usuario_id')
            ->select('usuario_id', 'usuario_nombre')->distinct()->orderBy('usuario_nombre')
            ->get()->map(fn($u) => ['id' => $u->usuario_id, 'nombre' => $u->usuario_nombre])->all();
    }

    private function describir(Auditoria $a, array $cambios, ?CajaMovimiento $m): string
    {
        if ($a->modelo === CajaMovimiento::class) {
            if ($m) {
                return $this->resumenMovimiento($m);
            }
            $datos = $cambios['creado'] ?? $cambios['eliminado'] ?? [];
            return $datos ? $this->resumenMovimiento((object) $datos) : "Movimiento #{$a->modelo_id}";
        }
        if ($a->modelo === \App\Models\CajaOficinaMovimiento::class) {
            $fila = \App\Models\CajaOficinaMovimiento::withTrashed()->find($a->modelo_id);
            return 'Oficina · ' . ($fila ? $this->resumenMovimiento($fila) : "fila #{$a->modelo_id}");
        }
        if ($a->modelo === \App\Models\CajaOficinaEnvio::class) {
            return "Envío #{$a->modelo_id} de la caja de oficina";
        }
        if ($a->modelo === CajaArqueo::class) {
            $fecha = $cambios['creado']['fecha'] ?? $cambios['eliminado']['fecha'] ?? null;
            return 'Arqueo' . ($fecha ? ' del ' . Carbon::parse($fecha)->format('d/m/Y') : " #{$a->modelo_id}");
        }
        $cierre = CajaCierre::find($a->modelo_id);
        return $cierre ? 'Caja de ' . Carbon::create($cierre->anio, $cierre->mes, 1)->locale('es')->translatedFormat('F Y') : 'Cierre de mes';
    }

    private function resumenMovimiento(object $m): string
    {
        $partes = [
            !empty($m->fecha) ? Carbon::parse($m->fecha)->format('d/m/Y') : null,
            !empty($m->es_contable) ? 'Contable' : (!empty($m->numero_caja) ? "Caja {$m->numero_caja}" : null),
            $m->beneficiario ?? null ?: null,
            mb_strimwidth((string) ($m->descripcion ?? ''), 0, 70, '…') ?: null,
            'S/ ' . number_format((float) ($m->importe ?? 0), 2),
        ];
        return implode(' · ', array_filter($partes));
    }

    /** @return array<int, array{campo:string, antes:?string, despues:?string}> */
    private function diferencias(array $cambios): array
    {
        $antes = $cambios['antes'] ?? [];
        $despues = $cambios['despues'] ?? [];
        $lista = [];
        foreach (array_unique(array_merge(array_keys($antes), array_keys($despues))) as $campo) {
            if (in_array($campo, self::OCULTOS, true)) {
                continue;
            }
            $lista[] = [
                'campo' => self::ETIQUETAS[$campo] ?? $campo,
                'antes' => $this->valor($campo, $antes[$campo] ?? null),
                'despues' => $this->valor($campo, $despues[$campo] ?? null),
            ];
        }
        return $lista;
    }

    private function valor(string $campo, $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($campo === 'fecha') {
            return Carbon::parse($v)->format('d/m/Y');
        }
        if (in_array($campo, ['es_contable', 'negrita'], true)) {
            return $v ? 'Sí' : 'No';
        }
        return is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
    }
}
