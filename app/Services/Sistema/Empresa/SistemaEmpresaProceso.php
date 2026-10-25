<?php

namespace App\Services\Sistema\Empresa;

use App\Exceptions\ErrorControlado;
use App\Models\Empresa;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la empresa de este sistema (una sola fila en `empresa`): razón social, RUC y establecimiento. Los usan los
 * encabezados de reportes y Excel. Se editan en Sistema → Configuración o en la hoja "Empresa" del Excel de recursos.
 */
class SistemaEmpresaProceso
{
    /** @return array{ruc: ?string, razon_social: ?string, establecimiento: ?string} */
    public static function datos(): array
    {
        $e = Empresa::first();
        return ['ruc' => $e?->ruc, 'razon_social' => $e?->razon_social, 'establecimiento' => $e?->establecimiento];
    }

    public function guardar(string $ruc, string $razonSocial, ?string $establecimiento): void
    {
        $ruc = preg_replace('/\D/', '', $ruc);
        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new ErrorControlado('El RUC debe tener 11 dígitos.');
        }
        if (trim($razonSocial) === '') {
            throw new ErrorControlado('La razón social es obligatoria.');
        }
        DB::transaction(function () use ($ruc, $razonSocial, $establecimiento) {
            $antes = self::datos();
            // Una sola empresa por sistema
            Empresa::where('ruc', '<>', $ruc)->delete();
            Empresa::updateOrCreate(['ruc' => $ruc], ['razon_social' => trim($razonSocial), 'establecimiento' => trim((string) $establecimiento) ?: '']);
            AuditoriaServicio::registrar(Empresa::class, $ruc, 'editar', $antes, self::datos(), 'Datos de la empresa');
        });
    }
}
