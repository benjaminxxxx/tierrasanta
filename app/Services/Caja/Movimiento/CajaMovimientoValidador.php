<?php

namespace App\Services\Caja\Movimiento;

use App\Models\CajaClasificador;
use App\Services\Caja\Cierre\CajaCierreConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Reglas para guardar un movimiento: datos obligatorios y mes abierto (un mes cerrado no se toca: ni sus
 * movimientos ni mover uno hacia él).
 */
class CajaMovimientoValidador
{
    public function __construct(private CajaCierreConsulta $cierres)
    {
    }

    public function validar(array $datos): array
    {
        $validator = Validator::make($datos, [
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
            'caja_clasificador_id' => 'required|exists:caja_clasificadores,id',
            'subgrupo_ng' => 'nullable|string|max:100',
            'subgrupo_bl' => 'nullable|string|max:100',
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
            'exists' => 'El :attribute no es válido.',
        ], [
            'caja_clasificador_id' => 'clasificador',
            'descripcion' => 'descripción (gastos B+N)',
            'importe_texto' => 'importe',
            'importe_usd' => 'importe en dólares',
            'tipo_cambio_operacion' => 'tipo de cambio del pago',
        ]);

        $validator->after(function ($v) use ($datos) {
            $clasificador = CajaClasificador::find($datos['caja_clasificador_id'] ?? null);
            if ($clasificador && isset($datos['tipo']) && $clasificador->tipo !== $datos['tipo']) {
                $v->errors()->add('caja_clasificador_id', "El clasificador es de {$clasificador->tipo}, pero el movimiento es {$datos['tipo']}.");
            }
            // El N° de caja se sugiere pero no es obligatorio: en el Excel hay ventas e ingresos sin número
        });

        return $validator->validate();
    }

    /** Lanza un error si la fecha está en un mes cerrado. */
    public function asegurarMesAbierto($fecha, string $accion = 'modificar'): void
    {
        if ($this->cierres->fechaCerrada($fecha)) {
            $mes = Carbon::parse($fecha)->locale('es')->translatedFormat('F Y');
            throw ValidationException::withMessages([
                'fecha' => "La caja de {$mes} está cerrada: no se puede {$accion}. Reabre el mes si hay que corregir algo.",
            ]);
        }
    }
}
