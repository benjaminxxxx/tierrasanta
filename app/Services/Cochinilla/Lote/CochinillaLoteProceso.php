<?php

namespace App\Services\Cochinilla\Lote;

use App\Exceptions\ErrorControlado;
use App\Models\CochinillaFiltrado;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaVenteado;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Correcciones de un lote de cochinilla ya registrado (número de lote, venteado, filtrado).
 * Todo cambio pide motivo y queda en la auditoría con el usuario y el antes/después.
 */
class CochinillaLoteProceso
{
    public const MOTIVO_MINIMO = 5;

    public static function exigirMotivo(?string $motivo): string
    {
        $motivo = trim((string) $motivo);
        if (mb_strlen($motivo) < self::MOTIVO_MINIMO) {
            throw new ErrorControlado('Indica el motivo del cambio (mínimo ' . self::MOTIVO_MINIMO . ' letras): queda en la auditoría.');
        }
        return $motivo;
    }

    /**
     * Cambia el número de lote (error de digitación): pasa con él sus sublotes, venteado y filtrado,
     * y adopta el venteado/filtrado que se registró con el número correcto y quedó sin ingreso.
     * La auditoría la registra quien llama (es parte de "Datos del lote").
     *
     * @return array{venteados:int, filtrados:int, venteados_adoptados:int, filtrados_adoptados:int}
     */
    public function cambiarNumero(CochinillaIngreso $ingreso, int $nuevo): array
    {
        $viejo = (int) $ingreso->lote;
        if ($nuevo < 1) {
            throw new ErrorControlado('El número de lote debe ser mayor a cero.');
        }
        if ($nuevo === $viejo) {
            return ['venteados' => 0, 'filtrados' => 0, 'venteados_adoptados' => 0, 'filtrados_adoptados' => 0];
        }
        $otro = CochinillaIngreso::where('lote', $nuevo)->where('id', '<>', $ingreso->id)->first();
        if ($otro) {
            throw new ErrorControlado("El lote {$nuevo} ya existe ({$otro->campo}, " . Carbon::parse($otro->fecha)->format('d/m/Y')
                . '). Si es un duplicado, elimina uno de los dos con su motivo.');
        }

        return DB::transaction(function () use ($ingreso, $viejo, $nuevo) {
            // Antes de tocar el ingreso: el trigger AFTER UPDATE de cochinilla_ingresos suelta lo vinculado
            // al lote viejo y une lo que tenga el lote nuevo, así que primero se anota qué es de cada uno.
            $venteadosPropios = CochinillaVenteado::where('cochinilla_ingreso_id', $ingreso->id)->pluck('id');
            $filtradosPropios = CochinillaFiltrado::where('cochinilla_ingreso_id', $ingreso->id)->pluck('id');
            $venteadosAdoptados = CochinillaVenteado::whereNull('cochinilla_ingreso_id')->where('lote', $nuevo)->count();
            $filtradosAdoptados = CochinillaFiltrado::whereNull('cochinilla_ingreso_id')->where('lote', $nuevo)->count();

            $ingreso->lote = $nuevo;
            $ingreso->save();

            foreach ($ingreso->detalles()->get() as $detalle) {
                $codigo = (string) $detalle->sublote_codigo;
                if (str_starts_with($codigo, $viejo . '.')) {
                    $detalle->sublote_codigo = $nuevo . substr($codigo, strlen((string) $viejo));
                    $detalle->save();
                }
            }

            // Lo que era del lote pasa al n° nuevo y se vuelve a unir; lo suelto con el n° correcto también
            // (se fija el id explícito además del trigger, por si la BD no tuviera los triggers)
            CochinillaVenteado::whereIn('id', $venteadosPropios)->update(['lote' => $nuevo, 'cochinilla_ingreso_id' => $ingreso->id]);
            CochinillaFiltrado::whereIn('id', $filtradosPropios)->update(['lote' => $nuevo, 'cochinilla_ingreso_id' => $ingreso->id]);
            CochinillaVenteado::where('lote', $nuevo)->whereNull('cochinilla_ingreso_id')->update(['cochinilla_ingreso_id' => $ingreso->id]);
            CochinillaFiltrado::where('lote', $nuevo)->whereNull('cochinilla_ingreso_id')->update(['cochinilla_ingreso_id' => $ingreso->id]);

            return [
                'venteados' => $venteadosPropios->count(),
                'filtrados' => $filtradosPropios->count(),
                'venteados_adoptados' => $venteadosAdoptados,
                'filtrados_adoptados' => $filtradosAdoptados,
            ];
        });
    }

    /** Venteado del lote como texto legible para la auditoría. */
    public static function lineasVenteado($venteados): array
    {
        return collect($venteados)->sortBy('fecha_proceso')->map(fn($v) => sprintf(
            '%s · %s kg ingresados · limpia %s · polvillo %s',
            Carbon::parse(data_get($v, 'fecha_proceso'))->format('d/m/Y'),
            (float) ($v['kilos_ingresado'] ?? 0),
            (float) ($v['limpia'] ?? 0),
            (float) ($v['polvillo'] ?? 0),
        ))->values()->all();
    }

    /** Filtrado del lote como texto legible para la auditoría. */
    public static function lineasFiltrado($filtrados): array
    {
        return collect($filtrados)->sortBy('fecha_proceso')->map(fn($f) => sprintf(
            '%s · %s kg ingresados · 1ra %s · 2da %s · 3ra %s · piedra %s',
            Carbon::parse(data_get($f, 'fecha_proceso'))->format('d/m/Y'),
            (float) ($f['kilos_ingresados'] ?? 0),
            (float) ($f['primera'] ?? 0),
            (float) ($f['segunda'] ?? 0),
            (float) ($f['tercera'] ?? 0),
            (float) ($f['piedra'] ?? 0),
        ))->values()->all();
    }

    /** Registra en la auditoría del ingreso el cambio de su venteado o filtrado. */
    public static function auditarProceso(int $ingresoId, int $lote, string $que, array $antes, array $despues, string $motivo): void
    {
        $clave = mb_strtolower($que);
        $accion = empty($despues) ? 'eliminar' : 'editar';
        $verbo = empty($despues) ? 'eliminado' : 'editado';
        AuditoriaServicio::registrar(
            modelo: CochinillaIngreso::class,
            modeloId: $ingresoId,
            accion: $accion,
            antes: [$clave => $antes],
            despues: $accion === 'editar' ? [$clave => $despues] : null,
            observacion: mb_substr("{$que} del lote {$lote} {$verbo}. Motivo: {$motivo}", 0, 255),
        );
    }

    /**
     * Corrige el n° de lote de una fila suelta de venteado o filtrado (se digitó mal y quedó sin ingreso):
     * el trigger la vincula al ingreso del lote nuevo.
     */
    public function corregirLoteFila(string $clase, int $id, ?int $nuevo, ?string $motivo): ?CochinillaIngreso
    {
        $motivo = self::exigirMotivo($motivo);
        if (!in_array($clase, [CochinillaVenteado::class, CochinillaFiltrado::class], true)) {
            throw new ErrorControlado('Tipo de registro no válido.');
        }
        if (!$nuevo || $nuevo < 1) {
            throw new ErrorControlado('Escribe el número de lote correcto.');
        }
        $fila = $clase::findOrFail($id);
        $viejo = (int) $fila->lote;
        if ($viejo === $nuevo) {
            throw new ErrorControlado("La fila ya tiene el lote {$nuevo}.");
        }
        $que = $clase === CochinillaVenteado::class ? 'Venteado' : 'Filtrado';
        $antes = $fila->toArray();
        $fila->lote = $nuevo;
        $fila->save();
        $fila->refresh();
        AuditoriaServicio::registrar(
            modelo: $clase,
            modeloId: $fila->id,
            accion: 'editar',
            antes: $antes,
            despues: $fila->toArray(),
            observacion: mb_substr("{$que}: lote {$viejo} corregido a {$nuevo}. Motivo: {$motivo}", 0, 255),
            camposIgnorados: ['updated_at', 'created_at'],
        );
        return $fila->cochinilla_ingreso_id ? CochinillaIngreso::find($fila->cochinilla_ingreso_id) : null;
    }

    /** Elimina una fila suelta de venteado (p. ej. sin ingreso) dejando copia en la auditoría. */
    public function eliminarVenteado(int $id, ?string $motivo): void
    {
        $motivo = self::exigirMotivo($motivo);
        $venteado = CochinillaVenteado::findOrFail($id);
        AuditoriaServicio::registrar(
            modelo: CochinillaVenteado::class,
            modeloId: $venteado->id,
            accion: 'eliminar',
            antes: $venteado->toArray(),
            observacion: mb_substr("Venteado del lote {$venteado->lote} eliminado. Motivo: {$motivo}", 0, 255),
        );
        $venteado->delete();
    }

    /** Elimina una fila suelta de filtrado dejando copia en la auditoría. */
    public function eliminarFiltrado(int $id, ?string $motivo): void
    {
        $motivo = self::exigirMotivo($motivo);
        $filtrado = CochinillaFiltrado::findOrFail($id);
        AuditoriaServicio::registrar(
            modelo: CochinillaFiltrado::class,
            modeloId: $filtrado->id,
            accion: 'eliminar',
            antes: $filtrado->toArray(),
            observacion: mb_substr("Filtrado del lote {$filtrado->lote} eliminado. Motivo: {$motivo}", 0, 255),
        );
        $filtrado->delete();
    }
}
