<?php

namespace App\Services\Caja\Movimiento;

use App\Models\CajaClasificador;
use App\Models\CajaMovimiento;
use App\Models\CajaTipoCambio;
use App\Models\Empresa;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Crear, editar, eliminar y pintar movimientos de caja. Solo se escribe desde aquí (el módulo de caja):
 * ningún otro módulo genera movimientos.
 */
class CajaMovimientoCrud
{
    private const IGNORAR_AUDITORIA = ['updated_at', 'created_at', 'actualizado_por', 'editado_manual'];

    public function __construct(private CajaMovimientoValidador $validador)
    {
    }

    public function crear(array $datos): CajaMovimiento
    {
        $limpios = $this->preparar($this->validador->validar($datos));
        $this->validador->asegurarMesAbierto($limpios['fecha'], 'registrar movimientos');

        return DB::transaction(function () use ($limpios) {
            $limpios['orden'] = (int) CajaMovimiento::whereDate('fecha', $limpios['fecha'])->max('orden') + 1;
            $limpios['creado_por'] = auth()->id();
            $movimiento = CajaMovimiento::create($limpios);
            AuditoriaServicio::registrar(CajaMovimiento::class, $movimiento->id, 'crear', null, $movimiento->toArray());
            return $movimiento;
        });
    }

    public function actualizar(int $id, array $datos): CajaMovimiento
    {
        $movimiento = CajaMovimiento::findOrFail($id);
        $limpios = $this->preparar($this->validador->validar($datos));
        $this->validador->asegurarMesAbierto($movimiento->fecha, 'editar sus movimientos');
        $this->validador->asegurarMesAbierto($limpios['fecha'], 'mover movimientos a ese mes');

        return DB::transaction(function () use ($movimiento, $limpios) {
            $antes = $movimiento->toArray();
            if (Carbon::parse($limpios['fecha'])->toDateString() !== $movimiento->fecha->toDateString()) {
                $limpios['orden'] = (int) CajaMovimiento::whereDate('fecha', $limpios['fecha'])->max('orden') + 1;
            }
            $limpios['actualizado_por'] = auth()->id();
            // Vino de la caja de oficina y se corrige aquí: un inverso ya corregido no se pisa con otro envío
            $limpios['editado_manual'] = $movimiento->editado_manual || $movimiento->caja_oficina_movimiento_id !== null;
            $movimiento->update($limpios);
            AuditoriaServicio::registrar(CajaMovimiento::class, $movimiento->id, 'editar', $antes, $movimiento->fresh()->toArray(), null, self::IGNORAR_AUDITORIA);
            return $movimiento;
        });
    }

    /** Se elimina con soft delete: queda quién lo eliminó, cuándo (deleted_at) y por qué. */
    public function eliminar(int $id, ?string $motivo): void
    {
        $motivo = trim((string) $motivo);
        if (mb_strlen($motivo) < 5) {
            throw \Illuminate\Validation\ValidationException::withMessages(['motivo' => 'Indica el motivo de la eliminación.']);
        }
        $movimiento = CajaMovimiento::findOrFail($id);
        $this->validador->asegurarMesAbierto($movimiento->fecha, 'eliminar sus movimientos');

        DB::transaction(function () use ($movimiento, $motivo) {
            AuditoriaServicio::registrar(CajaMovimiento::class, $movimiento->id, 'eliminar', $movimiento->toArray(), null, "Motivo: {$motivo}");
            $movimiento->update(['eliminado_por' => auth()->id(), 'motivo_eliminacion' => mb_substr($motivo, 0, 500)]);
            $movimiento->delete();
        });
    }

    /**
     * Color personalizado de una o varias filas (null = vuelve al color de su clasificador).
     *
     * @param int[] $ids
     */
    public function asignarColor(array $ids, ?string $fondo, ?string $texto, ?bool $negrita): int
    {
        $fondo = $this->color($fondo);
        $texto = $this->color($texto);
        $movimientos = CajaMovimiento::whereIn('id', $ids)->get();
        foreach ($movimientos as $m) {
            $this->validador->asegurarMesAbierto($m->fecha, 'cambiar el color de sus movimientos');
        }

        return DB::transaction(function () use ($movimientos, $fondo, $texto, $negrita) {
            foreach ($movimientos as $m) {
                $antes = $m->toArray();
                $m->update(['color_fondo' => $fondo, 'color_texto' => $texto, 'negrita' => $negrita, 'actualizado_por' => auth()->id()]);
                AuditoriaServicio::registrar(CajaMovimiento::class, $m->id, 'editar', $antes, $m->fresh()->toArray(), 'Color de fila', self::IGNORAR_AUDITORIA);
            }
            return $movimientos->count();
        });
    }

    /** Siguiente N° de caja del mes (los números se pueden repetir en varias filas de un mismo pago). */
    public function siguienteNumeroCaja($fecha): int
    {
        $f = Carbon::parse($fecha);
        return (int) CajaMovimiento::whereYear('fecha', $f->year)->whereMonth('fecha', $f->month)->max('numero_caja') + 1;
    }

    public function tipoCambioDelDia($fecha): ?float
    {
        $valor = CajaTipoCambio::where('fecha', '<=', Carbon::parse($fecha)->toDateString())->orderByDesc('fecha')->value('valor');
        return $valor !== null ? (float) $valor : null;
    }

    /** De los datos del formulario a columnas: importe con signo, clasificadores copiados, contable = BLA. */
    private function preparar(array $v): array
    {
        $clasificador = CajaClasificador::findOrFail($v['caja_clasificador_id']);
        $enDolares = (bool) ($v['pagado_en_dolares'] ?? false);

        if ($enDolares) {
            $importe = round((float) $v['importe_usd'] * (float) $v['tipo_cambio_operacion'], 2);
            $detalle = null;
        } else {
            $importe = CajaMovimientoReglas::evaluarOperacion($v['importe_texto']);
            $texto = ltrim(trim($v['importe_texto']), '=');
            $detalle = is_numeric(str_replace(',', '', $texto)) ? null : $texto;
        }
        // El signo lo da el tipo: se escribe el monto en positivo (una operación puede dar negativo: se respeta su valor absoluto)
        $importe = $v['tipo'] === 'EGRESO' ? -abs($importe) : abs($importe);

        $esContable = (bool) ($v['es_contable'] ?? false);

        return [
            'empresa' => Empresa::value('razon_social') ?? 'TSH SAC',
            'es_contable' => $esContable,
            'numero_caja' => $esContable || empty($v['numero_caja']) ? null : (int) $v['numero_caja'],
            'condicion' => $esContable ? 'BLA' : $v['condicion'],
            'categoria' => $this->texto($v['categoria'] ?? null) ?? ($esContable ? 'Contable' : null),
            'codigo' => $this->texto($v['codigo'] ?? null),
            'beneficiario' => $this->texto($v['beneficiario'] ?? null),
            'descripcion' => trim($v['descripcion']),
            'caja_clasificador_id' => $clasificador->id,
            'clasificador_1' => $clasificador->clasificador_1,
            'clasificador_2' => $clasificador->clasificador_2,
            'subgrupo_ng' => $this->texto($v['subgrupo_ng'] ?? null),
            'subgrupo_bl' => $this->texto($v['subgrupo_bl'] ?? null),
            'moneda' => CajaMovimientoReglas::MONEDA,
            'fecha' => Carbon::parse($v['fecha'])->toDateString(),
            'semana' => (int) $v['semana'],
            'tipo_documento' => $this->texto($v['tipo_documento'] ?? null),
            'numero_documento' => $this->texto($v['numero_documento'] ?? null),
            'situacion_cheque' => $this->texto($v['situacion_cheque'] ?? null),
            'importe_usd' => $enDolares ? round((float) $v['importe_usd'], 2) * ($v['tipo'] === 'EGRESO' ? -1 : 1) : null,
            'tipo_cambio_operacion' => $enDolares ? (float) $v['tipo_cambio_operacion'] : null,
            'importe' => $importe,
            'importe_detalle' => $detalle,
            'es_saldo_inicial' => CajaMovimientoReglas::esSaldoInicial($clasificador->clasificador_1),
            'tipo_cambio' => isset($v['tipo_cambio']) && $v['tipo_cambio'] !== '' ? (float) $v['tipo_cambio'] : $this->tipoCambioDelDia($v['fecha']),
        ];
    }

    private function texto($valor): ?string
    {
        $valor = is_string($valor) ? trim($valor) : $valor;
        return $valor === '' || $valor === null ? null : (string) $valor;
    }

    private function color(?string $color): ?string
    {
        $color = $color ? strtoupper(trim($color)) : null;
        if ($color && !preg_match('/^#[0-9A-F]{6}$/', $color)) {
            throw new \InvalidArgumentException("Color no válido: {$color}");
        }
        return $color ?: null;
    }
}
