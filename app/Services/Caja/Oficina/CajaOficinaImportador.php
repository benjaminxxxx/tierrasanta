<?php

namespace App\Services\Caja\Oficina;

use App\Models\CajaOficinaMovimiento;
use App\Models\Empresa;
use App\Services\Caja\Cierre\CajaCierreConsulta;
use App\Services\Caja\Importacion\CajaImportacionExcel;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Carga la caja de oficina desde el mismo Excel de caja (hoja BASE), sin clasificadores, sub-grupos ni
 * saldos por fuente. Solo agrega las filas que faltan (por huella): se puede subir el mismo Excel varias
 * veces. Los meses cerrados no se tocan.
 *
 * Las filas que ya están en la caja de movimientos quedan vinculadas y como enviadas; las demás quedan
 * pendientes de envío.
 */
class CajaOficinaImportador
{
    public function __construct(private CajaImportacionExcel $excel, private CajaCierreConsulta $cierres, private CajaOficinaProceso $proceso)
    {
    }

    /**
     * @return array{movimientos:int, existentes:int, solo_sistema:int, vinculadas:int, por_enviar:int, meses_omitidos:string[],
     *               desde:string, hasta:string, saldo_final:float}
     */
    public function importar(string $ruta): array
    {
        $todas = $this->excel->leerMovimientos($ruta);
        if (!$todas) {
            throw ValidationException::withMessages(['archivo' => 'La hoja BASE no tiene movimientos.']);
        }
        $desde = min(array_column($todas, 'fecha'));
        $hasta = max(array_column($todas, 'fecha'));

        $cerrado = [];
        foreach ($todas as $f) {
            $cerrado[substr($f['fecha'], 0, 7)] ??= $this->cierres->fechaCerrada($f['fecha']);
        }
        $filas = array_filter($todas, fn($f) => !$cerrado[substr($f['fecha'], 0, 7)]);
        $mesesAbiertos = array_keys(array_filter($cerrado, fn($c) => !$c));

        return DB::transaction(function () use ($filas, $desde, $hasta, $cerrado, $mesesAbiertos) {
            // Las eliminadas también cuentan: lo que se borró con motivo no revive al subir el Excel otra vez
            $enSistema = CajaOficinaMovimiento::withTrashed()->whereBetween('fecha', [$desde, $hasta])
                ->whereIn(DB::raw("DATE_FORMAT(fecha, '%Y-%m')"), $mesesAbiertos ?: ['-'])->get()
                ->countBy(fn($m) => CajaMovimientoReglas::huella($m->fecha->toDateString(), (float) $m->importe, $m->beneficiario, $m->descripcion))->all();

            $empresa = Empresa::value('razon_social') ?? 'TSH SAC';
            $ahora = now();
            $existentes = 0;
            $insertar = [];
            foreach ($filas as $f) {
                $h = CajaMovimientoReglas::huella($f['fecha'], $f['importe'], $f['beneficiario'], $f['descripcion']);
                if (($enSistema[$h] ?? 0) > 0) {
                    $enSistema[$h]--;
                    $existentes++;
                    continue;
                }
                $insertar[] = [
                    'empresa' => $empresa,
                    'numero_caja' => $f['numero_caja'],
                    'es_contable' => $f['es_contable'],
                    'condicion' => $f['condicion'],
                    'categoria' => $f['categoria'],
                    'codigo' => $f['codigo'],
                    'beneficiario' => $f['beneficiario'],
                    'descripcion' => $f['descripcion'],
                    'moneda' => CajaMovimientoReglas::MONEDA,
                    'fecha' => $f['fecha'],
                    'semana' => $f['semana'] ?: CajaMovimientoReglas::semanaDelMes($f['fecha']),
                    'tipo_documento' => $f['tipo_documento'],
                    'numero_documento' => $f['numero_documento'],
                    'situacion_cheque' => $f['situacion_cheque'],
                    'importe_usd' => $f['importe_usd'],
                    'tipo_cambio_operacion' => $f['tipo_cambio_operacion'],
                    'importe' => $f['importe'],
                    'importe_detalle' => $f['importe_detalle'],
                    'tipo_cambio' => $f['tipo_cambio'],
                    'es_saldo_inicial' => CajaMovimientoReglas::esSaldoInicial($f['clasificador_1']),
                    'orden' => $f['fila'],
                    'pendiente_envio' => true,
                    'creado_por' => auth()->id(),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
            foreach (array_chunk($insertar, 500) as $lote) {
                CajaOficinaMovimiento::insert($lote);
            }
            $vinculadas = $this->proceso->vincularPorHuella();

            $resultado = [
                'movimientos' => count($insertar),
                'existentes' => $existentes,
                'solo_sistema' => array_sum($enSistema),
                'vinculadas' => $vinculadas,
                'por_enviar' => $this->proceso->porEnviar(),
                'meses_omitidos' => array_map(fn($m) => Carbon::parse("{$m}-01")->locale('es')->translatedFormat('F Y'), array_keys(array_filter($cerrado))),
                'desde' => $desde,
                'hasta' => $hasta,
                'saldo_final' => round((float) CajaOficinaMovimiento::where('fecha', '<=', $hasta)->sum('importe'), 2),
            ];
            AuditoriaServicio::registrar(CajaOficinaMovimiento::class, 0, 'crear', null, $resultado, 'Importación de la caja de oficina desde Excel');
            return $resultado;
        });
    }
}
