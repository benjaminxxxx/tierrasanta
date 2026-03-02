<?php

namespace App\Services\Riego;
use App\Models\ReporteDiarioRiego as RegistroDiario;
use App\Models\ConsolidadoRiego as ResumenJornada;
use App\Support\FormatoHelper;
// app/Services/Riego/RegistroDiarioServicio.php
class RegistroDiarioServicio
{
    // Sin transaction — el proceso lo envuelve

    public function reemplazarRegistros(ResumenJornada $resumen, string $fecha, array $data, array $mapaCampos): void
    {
        $resumen->registrosDiarios()
            ->where('por_acumulacion', false)
            ->get()
            ->each->delete(); // dispara observer

        foreach ($data as $row) {
            if (empty($row[0])) continue;

            $alias = mb_strtolower(trim($row[0]));
            $campo = $mapaCampos[$alias] ?? $row[0];

            RegistroDiario::create([
                'consolidado_id'   => $resumen->id,
                'campo'            => $campo,
                'hora_inicio'      => FormatoHelper::normalizarHora($row[1] ?? '00:00'),
                'hora_fin'         => FormatoHelper::normalizarHora($row[2] ?? '00:00'),
                'fecha'            => $fecha,
                'documento'        => '',
                'regador'          => '',
                'sh'               => isset($row[6]) ? (bool)$row[6] : false,
                'tipo_labor'       => isset($row[4]) && trim($row[4]) !== '' ? $row[4] : 'Riego',
                'descripcion'      => $row[5] ?? null,
                'por_acumulacion'  => false,
            ]);
        }
    }
}