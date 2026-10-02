<?php

namespace App\Services\Caja\Oficina;

use App\Models\CajaOficinaMovimiento;
use App\Models\Empresa;
use App\Services\Caja\Movimiento\CajaMovimientoCrud;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Services\Caja\Movimiento\CajaMovimientoValidador;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Crear, editar y eliminar movimientos de la caja de oficina, y generar el inverso de una fila cuando el
 * dinero no entra realmente a la caja (efecto cero).
 *
 * Nada de aquí escribe en la caja de movimientos: cada cambio queda pendiente de envío (CajaOficinaProceso).
 */
class CajaOficinaCrud
{
    private const IGNORAR_AUDITORIA = ['updated_at', 'created_at', 'actualizado_por', 'pendiente_envio', 'enviado_at', 'ultimo_enviado'];

    public function __construct(private CajaMovimientoValidador $validador, private CajaMovimientoCrud $movimientos)
    {
    }

    public function crear(array $datos): CajaOficinaMovimiento
    {
        $limpios = $this->preparar($this->validar($datos));
        $this->validador->asegurarMesAbierto($limpios['fecha'], 'registrar movimientos');

        return DB::transaction(function () use ($limpios) {
            $limpios['orden'] = (int) CajaOficinaMovimiento::whereDate('fecha', $limpios['fecha'])->max('orden') + 1;
            $limpios['creado_por'] = auth()->id();
            $movimiento = CajaOficinaMovimiento::create($limpios);
            AuditoriaServicio::registrar(CajaOficinaMovimiento::class, $movimiento->id, 'crear', null, $movimiento->toArray(), null, self::IGNORAR_AUDITORIA);
            return $movimiento;
        });
    }

    public function actualizar(int $id, array $datos): CajaOficinaMovimiento
    {
        $movimiento = CajaOficinaMovimiento::findOrFail($id);
        if ($movimiento->inverso_de_id) {
            throw ValidationException::withMessages(['fecha' => 'Esta fila es el inverso de otra: se actualiza sola al editar la original. Si ya no corresponde, elimínala.']);
        }
        $limpios = $this->preparar($this->validar($datos));
        $this->validador->asegurarMesAbierto($movimiento->fecha, 'editar sus movimientos');
        $this->validador->asegurarMesAbierto($limpios['fecha'], 'mover movimientos a ese mes');

        return DB::transaction(function () use ($movimiento, $limpios) {
            $antes = $movimiento->toArray();
            if ($limpios['fecha'] !== $movimiento->fecha->toDateString()) {
                $limpios['orden'] = (int) CajaOficinaMovimiento::whereDate('fecha', $limpios['fecha'])->max('orden') + 1;
            }
            $movimiento->update($limpios + ['actualizado_por' => auth()->id(), 'pendiente_envio' => true]);
            AuditoriaServicio::registrar(CajaOficinaMovimiento::class, $movimiento->id, 'editar', $antes, $movimiento->fresh()->toArray(), null, self::IGNORAR_AUDITORIA);

            // El inverso sigue a su original: siempre suman cero
            if ($inverso = $movimiento->inverso) {
                $antesInverso = $inverso->toArray();
                $inverso->update($this->datosInverso($movimiento) + ['actualizado_por' => auth()->id(), 'pendiente_envio' => true]);
                AuditoriaServicio::registrar(CajaOficinaMovimiento::class, $inverso->id, 'editar', $antesInverso, $inverso->fresh()->toArray(),
                    'Inverso actualizado con su original', self::IGNORAR_AUDITORIA);
            }
            return $movimiento;
        });
    }

    /**
     * "El dinero no entra realmente a caja": crea la fila contraria, vinculada, para que el efecto sea cero.
     */
    public function generarInverso(int $id): CajaOficinaMovimiento
    {
        $movimiento = CajaOficinaMovimiento::findOrFail($id);
        if ($movimiento->inverso_de_id) {
            throw ValidationException::withMessages(['fecha' => 'Esta fila ya es el inverso de otra.']);
        }
        if ($movimiento->es_saldo_inicial) {
            throw ValidationException::withMessages(['fecha' => 'El saldo anterior no tiene inverso.']);
        }
        if ($movimiento->inverso()->exists()) {
            throw ValidationException::withMessages(['fecha' => 'Esta fila ya tiene su inverso.']);
        }
        $this->validador->asegurarMesAbierto($movimiento->fecha, 'generar inversos');

        return DB::transaction(function () use ($movimiento) {
            $inverso = CajaOficinaMovimiento::create($this->datosInverso($movimiento) + [
                'empresa' => $movimiento->empresa,
                'moneda' => $movimiento->moneda,
                'inverso_de_id' => $movimiento->id,
                // Mismo orden que su original: al ordenar por id queda justo debajo
                'orden' => $movimiento->orden,
                'creado_por' => auth()->id(),
            ]);
            AuditoriaServicio::registrar(CajaOficinaMovimiento::class, $inverso->id, 'crear', null, $inverso->toArray(),
                "Inverso de la fila #{$movimiento->id}: el dinero no entra realmente a caja", self::IGNORAR_AUDITORIA);
            return $inverso;
        });
    }

    /** Soft delete con motivo. Al eliminar una fila se va también su inverso (solo no tendría sentido). */
    public function eliminar(int $id, ?string $motivo): void
    {
        $motivo = trim((string) $motivo);
        if (mb_strlen($motivo) < 5) {
            throw ValidationException::withMessages(['motivo' => 'Indica el motivo de la eliminación.']);
        }
        $movimiento = CajaOficinaMovimiento::findOrFail($id);
        $this->validador->asegurarMesAbierto($movimiento->fecha, 'eliminar sus movimientos');

        DB::transaction(function () use ($movimiento, $motivo) {
            foreach (array_filter([$movimiento, $movimiento->inverso]) as $fila) {
                AuditoriaServicio::registrar(CajaOficinaMovimiento::class, $fila->id, 'eliminar', $fila->toArray(), null, "Motivo: {$motivo}", self::IGNORAR_AUDITORIA);
                $fila->update(['eliminado_por' => auth()->id(), 'motivo_eliminacion' => mb_substr($motivo, 0, 500), 'pendiente_envio' => true]);
                $fila->delete();
            }
        });
    }

    public function siguienteNumeroCaja($fecha): int
    {
        $f = Carbon::parse($fecha);
        return (int) CajaOficinaMovimiento::whereYear('fecha', $f->year)->whereMonth('fecha', $f->month)->max('numero_caja') + 1;
    }

    private function datosInverso(CajaOficinaMovimiento $m): array
    {
        return [
            'numero_caja' => $m->numero_caja,
            'es_contable' => $m->es_contable,
            'condicion' => $m->condicion,
            'categoria' => $m->categoria,
            'codigo' => $m->codigo,
            'beneficiario' => $m->beneficiario,
            'descripcion' => $m->descripcion,
            'fecha' => $m->fecha->toDateString(),
            'semana' => $m->semana,
            'tipo_documento' => $m->tipo_documento,
            'numero_documento' => $m->numero_documento,
            'situacion_cheque' => $m->situacion_cheque,
            'importe_usd' => $m->importe_usd !== null ? -1 * (float) $m->importe_usd : null,
            'tipo_cambio_operacion' => $m->tipo_cambio_operacion,
            'importe' => -1 * (float) $m->importe,
            'importe_detalle' => null,
            'tipo_cambio' => $m->tipo_cambio,
        ];
    }

    private function validar(array $datos): array
    {
        return Validator::make($datos, [
            'fecha' => 'required|date',
            'semana' => 'required|integer|min:1|max:6',
            'tipo' => 'required|in:INGRESO,EGRESO',
            'es_contable' => 'boolean',
            'numero_caja' => 'nullable|integer|min:1',
            'condicion' => 'required|in:NEG,BLA',
            'categoria' => 'nullable|string|max:100',
            'codigo' => 'nullable|string|max:100',
            'beneficiario' => 'nullable|string|max:200',
            'descripcion' => 'required|string|max:2000',
            'tipo_documento' => 'nullable|string|max:2000',
            'numero_documento' => 'nullable|string|max:200',
            'situacion_cheque' => 'nullable|string|max:150',
            'pagado_en_dolares' => 'boolean',
            'importe_usd' => 'nullable|required_if:pagado_en_dolares,true|numeric|gt:0',
            'tipo_cambio_operacion' => 'nullable|required_if:pagado_en_dolares,true|numeric|gt:0',
            'importe_texto' => 'nullable|required_unless:pagado_en_dolares,true|string|max:255',
            'tipo_cambio' => 'nullable|numeric|gt:0',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio si se pagó en dólares.',
            'required_unless' => 'El campo :attribute es obligatorio.',
        ], [
            'descripcion' => 'descripción (gastos B+N)',
            'importe_texto' => 'importe',
            'importe_usd' => 'importe en dólares',
            'tipo_cambio_operacion' => 'tipo de cambio del pago',
        ])->validate();
    }

    /** Igual que en la caja de movimientos, sin clasificadores ni sub-grupos. */
    private function preparar(array $v): array
    {
        $enDolares = (bool) ($v['pagado_en_dolares'] ?? false);
        if ($enDolares) {
            $importe = round((float) $v['importe_usd'] * (float) $v['tipo_cambio_operacion'], 2);
            $detalle = null;
        } else {
            $importe = CajaMovimientoReglas::evaluarOperacion($v['importe_texto']);
            $texto = ltrim(trim($v['importe_texto']), '=');
            $detalle = is_numeric(str_replace(',', '', $texto)) ? null : $texto;
        }
        $signo = $v['tipo'] === 'EGRESO' ? -1 : 1;
        $esContable = (bool) ($v['es_contable'] ?? false);
        $texto = fn($valor) => trim((string) $valor) === '' ? null : trim((string) $valor);

        return [
            'empresa' => Empresa::value('razon_social') ?? 'TSH SAC',
            'es_contable' => $esContable,
            'numero_caja' => $esContable || empty($v['numero_caja']) ? null : (int) $v['numero_caja'],
            'condicion' => $esContable ? 'BLA' : $v['condicion'],
            'categoria' => $texto($v['categoria'] ?? null) ?? ($esContable ? 'Contable' : null),
            'codigo' => $texto($v['codigo'] ?? null),
            'beneficiario' => $texto($v['beneficiario'] ?? null),
            'descripcion' => trim($v['descripcion']),
            'moneda' => CajaMovimientoReglas::MONEDA,
            'fecha' => Carbon::parse($v['fecha'])->toDateString(),
            'semana' => (int) $v['semana'],
            'tipo_documento' => $texto($v['tipo_documento'] ?? null),
            'numero_documento' => $texto($v['numero_documento'] ?? null),
            'situacion_cheque' => $texto($v['situacion_cheque'] ?? null),
            'importe_usd' => $enDolares ? $signo * round((float) $v['importe_usd'], 2) : null,
            'tipo_cambio_operacion' => $enDolares ? (float) $v['tipo_cambio_operacion'] : null,
            'importe' => $signo * abs($importe),
            'importe_detalle' => $detalle,
            'tipo_cambio' => !empty($v['tipo_cambio']) ? (float) $v['tipo_cambio'] : $this->movimientos->tipoCambioDelDia($v['fecha']),
        ];
    }
}
