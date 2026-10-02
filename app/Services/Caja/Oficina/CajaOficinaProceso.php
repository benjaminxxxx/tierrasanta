<?php

namespace App\Services\Caja\Oficina;

use App\Models\CajaMovimiento;
use App\Models\CajaOficinaEnvio;
use App\Models\CajaOficinaEnvioDetalle;
use App\Models\CajaOficinaMovimiento;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Services\Caja\Movimiento\CajaMovimientoValidador;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El puente entre las dos cajas:
 * - enviar: la caja de oficina junta lo que cambió desde el último envío (filas nuevas, modificadas y
 *   eliminadas) en un envío. Los envíos se acumulan mientras no se anexen.
 * - anexar: en la caja de movimientos se acepta un envío. Cada fila de oficina tiene una sola fila allá
 *   (caja_movimientos.caja_oficina_movimiento_id): una modificación actualiza esa misma fila, no crea otra.
 *
 * Un inverso que ya se corrigió en la caja de movimientos (1600 → =1600-500-200) no se vuelve a tocar.
 */
class CajaOficinaProceso
{
    public function __construct(private CajaMovimientoValidador $validador)
    {
    }

    /** Filas de oficina con cambios sin enviar. */
    public function porEnviar(): int
    {
        return CajaOficinaMovimiento::withTrashed()->where('pendiente_envio', true)
            ->where(fn($q) => $q->whereNull('deleted_at')->orWhereNotNull('enviado_at'))->count();
    }

    public function enviar(?string $nota = null): CajaOficinaEnvio
    {
        return DB::transaction(function () use ($nota) {
            $filas = CajaOficinaMovimiento::withTrashed()->where('pendiente_envio', true)
                ->orderBy('fecha')->orderBy('orden')->orderBy('id')->lockForUpdate()->get();

            $detalles = [];
            foreach ($filas as $fila) {
                $datos = $fila->datosEnvio();
                $accion = $fila->trashed() ? 'eliminado' : ($fila->enviado_at ? 'modificado' : 'nuevo');
                // Creada y eliminada sin haberse enviado, o guardada sin cambiar nada: no hay nada que informar
                $sinNovedad = ($accion === 'eliminado' && !$fila->enviado_at) || ($accion === 'modificado' && $datos == $fila->ultimo_enviado);
                if (!$sinNovedad) {
                    $detalles[] = [
                        'caja_oficina_movimiento_id' => $fila->id,
                        'accion' => $accion,
                        'datos' => $datos + ['es_inverso' => $fila->inverso_de_id !== null, 'motivo_eliminacion' => $fila->motivo_eliminacion],
                        'datos_antes' => $accion === 'nuevo' ? null : $fila->ultimo_enviado,
                    ];
                }
                $fila->timestamps = false;
                $fila->update(['pendiente_envio' => false] + ($sinNovedad ? [] : ['enviado_at' => now(), 'ultimo_enviado' => $datos]));
            }
            if (!$detalles) {
                throw ValidationException::withMessages(['envio' => 'No hay cambios por enviar.']);
            }

            $envio = CajaOficinaEnvio::create([
                'estado' => CajaOficinaEnvio::PENDIENTE,
                'nota' => trim((string) $nota) ?: null,
                'cambios' => count($detalles),
                'enviado_por' => auth()->id(),
                'enviado_nombre' => auth()->user()?->name,
            ]);
            foreach ($detalles as $d) {
                $envio->detalles()->create($d);
            }
            AuditoriaServicio::registrar(CajaOficinaEnvio::class, $envio->id, 'crear', null, ['cambios' => count($detalles), 'nota' => $envio->nota],
                'Envío de la caja de oficina a la caja de movimientos');
            return $envio;
        });
    }

    /**
     * Anexa un envío a la caja de movimientos. Los envíos se anexan en orden: uno posterior puede modificar
     * filas que trae uno anterior.
     *
     * @return array{anexados:int, omitidos:int}
     */
    public function anexar(int $envioId): array
    {
        return DB::transaction(function () use ($envioId) {
            $envio = CajaOficinaEnvio::with('detalles')->lockForUpdate()->findOrFail($envioId);
            if ($envio->estado !== CajaOficinaEnvio::PENDIENTE) {
                throw ValidationException::withMessages(['envio' => 'Ese envío ya fue anexado.']);
            }
            if (CajaOficinaEnvio::where('estado', CajaOficinaEnvio::PENDIENTE)->where('id', '<', $envio->id)->exists()) {
                throw ValidationException::withMessages(['envio' => 'Hay envíos anteriores sin anexar: anéxalos primero.']);
            }

            $quien = $envio->enviado_nombre ?: 'la caja de oficina';
            $origen = "Envío #{$envio->id} de {$quien}";
            $resumen = ['anexados' => 0, 'omitidos' => 0];
            foreach ($envio->detalles as $detalle) {
                [$resultado, $nota, $movimientoId] = $this->aplicar($detalle, $origen);
                $detalle->update(['resultado' => $resultado, 'resultado_nota' => $nota, 'caja_movimiento_id' => $movimientoId]);
                $resumen[$resultado === 'anexado' ? 'anexados' : 'omitidos']++;
            }

            $envio->update([
                'estado' => CajaOficinaEnvio::ANEXADO,
                'anexado_por' => auth()->id(),
                'anexado_nombre' => auth()->user()?->name,
                'anexado_at' => now(),
            ]);
            AuditoriaServicio::registrar(CajaOficinaEnvio::class, $envio->id, 'editar', ['estado' => CajaOficinaEnvio::PENDIENTE],
                ['estado' => CajaOficinaEnvio::ANEXADO] + $resumen, "{$origen} anexado a la caja de movimientos");
            return $resumen;
        });
    }

    /** Anexa todos los envíos pendientes, del más antiguo al más reciente. */
    public function anexarTodos(): array
    {
        return DB::transaction(function () {
            $total = ['anexados' => 0, 'omitidos' => 0, 'envios' => 0];
            foreach (CajaOficinaEnvio::where('estado', CajaOficinaEnvio::PENDIENTE)->orderBy('id')->pluck('id') as $id) {
                $r = $this->anexar($id);
                $total['anexados'] += $r['anexados'];
                $total['omitidos'] += $r['omitidos'];
                $total['envios']++;
            }
            return $total;
        });
    }

    /**
     * Vincula las filas de oficina con las de la caja de movimientos que son la misma (fecha, importe,
     * beneficiario y descripción), cuando las dos cajas se cargan del mismo Excel. Las vinculadas quedan
     * como ya enviadas: no generan un envío.
     */
    public function vincularPorHuella(): int
    {
        $vinculadas = CajaMovimiento::withTrashed()->whereNotNull('caja_oficina_movimiento_id')->pluck('caja_oficina_movimiento_id')->flip();
        $sueltas = CajaOficinaMovimiento::whereNull('enviado_at')->get()->reject(fn($f) => isset($vinculadas[$f->id]));
        if ($sueltas->isEmpty()) {
            return 0;
        }

        $libres = [];
        CajaMovimiento::whereNull('caja_oficina_movimiento_id')
            ->whereBetween('fecha', [$sueltas->min('fecha'), $sueltas->max('fecha')])
            ->orderBy('id')->get(['id', 'fecha', 'importe', 'beneficiario', 'descripcion'])
            ->each(function ($m) use (&$libres) {
                $libres[CajaMovimientoReglas::huella($m->fecha->toDateString(), (float) $m->importe, $m->beneficiario, $m->descripcion)][] = $m->id;
            });

        $n = 0;
        foreach ($sueltas as $fila) {
            $h = CajaMovimientoReglas::huella($fila->fecha->toDateString(), (float) $fila->importe, $fila->beneficiario, $fila->descripcion);
            if (empty($libres[$h])) {
                continue;
            }
            CajaMovimiento::whereKey(array_shift($libres[$h]))->update(['caja_oficina_movimiento_id' => $fila->id]);
            $fila->timestamps = false;
            $fila->update(['pendiente_envio' => false, 'enviado_at' => now(), 'ultimo_enviado' => $fila->datosEnvio()]);
            $n++;
        }
        return $n;
    }

    /** @return array{0:string, 1:?string, 2:?int} resultado, nota, id en caja_movimientos */
    private function aplicar(CajaOficinaEnvioDetalle $detalle, string $origen): array
    {
        $datos = array_intersect_key($detalle->datos, array_flip(CajaOficinaMovimiento::CAMPOS_ENVIO));
        $esInverso = (bool) ($detalle->datos['es_inverso'] ?? false);
        $actual = CajaMovimiento::withTrashed()->where('caja_oficina_movimiento_id', $detalle->caja_oficina_movimiento_id)->first();

        if ($actual?->trashed()) {
            return ['omitido', 'La fila se eliminó en la caja de movimientos: no se revive.', $actual->id];
        }
        if ($actual && $esInverso && $actual->editado_manual) {
            return ['omitido', 'El inverso ya se corrigió en la caja de movimientos: se conserva como está.', $actual->id];
        }

        if ($detalle->accion === 'eliminado') {
            if (!$actual) {
                return ['omitido', 'No estaba en la caja de movimientos.', null];
            }
            $this->validador->asegurarMesAbierto($actual->fecha, 'anexar una eliminación de ese mes');
            $motivo = "Eliminado en la caja de oficina ({$origen})" . (!empty($detalle->datos['motivo_eliminacion']) ? ": {$detalle->datos['motivo_eliminacion']}" : '');
            AuditoriaServicio::registrar(CajaMovimiento::class, $actual->id, 'eliminar', $actual->toArray(), null, "Motivo: {$motivo}");
            $actual->update(['eliminado_por' => auth()->id(), 'motivo_eliminacion' => mb_substr($motivo, 0, 500)]);
            $actual->delete();
            return ['anexado', null, $actual->id];
        }

        $this->validador->asegurarMesAbierto($datos['fecha'], 'anexar movimientos de ese mes');

        if (!$actual) {
            $nuevo = CajaMovimiento::create($datos + [
                'empresa' => \App\Models\Empresa::value('razon_social') ?? 'TSH SAC',
                'moneda' => CajaMovimientoReglas::MONEDA,
                'caja_oficina_movimiento_id' => $detalle->caja_oficina_movimiento_id,
                'orden' => (int) CajaMovimiento::whereDate('fecha', $datos['fecha'])->max('orden') + 1,
                'creado_por' => auth()->id(),
            ]);
            AuditoriaServicio::registrar(CajaMovimiento::class, $nuevo->id, 'crear', null, $nuevo->toArray(), "Anexado de la caja de oficina ({$origen})");
            return ['anexado', null, $nuevo->id];
        }

        // Solo lo que cambió en oficina desde el envío anterior: lo corregido aquí en otros campos se conserva
        $antes = $detalle->datos_antes ?? [];
        $cambios = array_filter($datos, fn($valor, $campo) => !array_key_exists($campo, $antes) || $antes[$campo] != $valor, ARRAY_FILTER_USE_BOTH);
        if (!$cambios) {
            return ['omitido', 'Sin cambios en los datos que se envían.', $actual->id];
        }
        $this->validador->asegurarMesAbierto($actual->fecha, 'anexar cambios a ese mes');
        $previo = $actual->toArray();
        if (isset($cambios['fecha'])) {
            $cambios['orden'] = (int) CajaMovimiento::whereDate('fecha', $cambios['fecha'])->max('orden') + 1;
        }
        $actual->update($cambios);
        AuditoriaServicio::registrar(CajaMovimiento::class, $actual->id, 'editar', $previo, $actual->fresh()->toArray(),
            "Modificado en la caja de oficina ({$origen})", ['updated_at', 'created_at']);
        return ['anexado', null, $actual->id];
    }
}
