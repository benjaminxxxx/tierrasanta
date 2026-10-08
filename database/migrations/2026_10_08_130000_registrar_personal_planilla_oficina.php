<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Datos del personal de oficina tomados de la planilla oficial de enero 2026 (hojas ADM y EMPLEADOS de
 * "PLANILLA MENSUAL ENERO 2026 KKK.xlsx"):
 * - Cuenta donde se paga lo de planilla (blanco) y la secundaria donde se paga la diferencia, en su contrato vigente.
 * - Victor Salas Acosta: se le paga por recibo por honorarios (RxH) y su contrato de oficina terminó en 2018: se le
 *   registra un contrato de oficina por honorarios desde 01/2026, sin retención de 4ta (la hoja le paga el monto completo).
 * - Alejandro Alcázar: remuneración básica 10,000 (la del contrato estaba en 0).
 * Solo completa lo que está vacío: no pisa datos que ya se hayan cargado.
 */
return new class extends Migration {
    /** DNI => [cuenta principal (blanco), cuenta secundaria (diferencia)] */
    private const CUENTAS = [
        '29597944' => ['BBVA 0011-0764-0100013899', null],                      // Salas Acosta Victor (RxH)
        '29485339' => ['BCP $ 215-07286821-1-21', null],                        // Chirinos Montesinos Patricio
        '18203595' => ['BCP 215-29881758-0-89', null],                          // Camones Zegarra Lucio
        '70007222' => ['BCP 215-30753767-0-06', null],                          // Alcázar Chirinos Alejandro
        '29682313' => ['BCP 215-90263570-0-18', 'Interbank 300-3193930959'],    // Escarcina Calderón Jacqueline
        '29709654' => ['BCP 215-18810969-0-74', 'BCP 215-18810969-0-74'],       // Mamani Supo Arcadio
        '71315740' => ['BCP 215-39109660-0-02', 'BCP 215-71209585-0-68'],       // Callasi Calla Geraldini
        '41438006' => ['BCP 215-96456302-0-03', 'BCP 215-96456297-0-97'],       // Mayta Salas Katherine
        '42990503' => ['BCP 215-37998589-0-07', 'BCP 215-00559796-0-45'],       // Acrota Quispe Paola
    ];

    public function up(): void
    {
        $victor = DB::table('plan_empleados')->where('documento', '29597944')->value('id');
        if ($victor && !$this->contratoVigente($victor)) {
            DB::table('plan_contratos')->insert([
                'plan_empleado_id' => $victor,
                'tipo_contrato' => 'indefinido',
                'fecha_inicio' => '2026-01-01',
                'tipo_planilla' => 'oficina',
                'tipo_ingreso' => 'honorarios',
                'suspension_cuarta' => true,
                'remuneracion_basica' => 9500,
                'modalidad_pago' => 'mensual',
                'esta_jubilado' => false,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $alejandro = DB::table('plan_empleados')->where('documento', '70007222')->value('id');
        if ($alejandro && ($c = $this->contratoVigente($alejandro)) && (float) $c->remuneracion_basica <= 0) {
            DB::table('plan_contratos')->where('id', $c->id)->update(['remuneracion_basica' => 10000, 'updated_at' => now()]);
        }

        foreach (self::CUENTAS as $dni => [$principal, $secundaria]) {
            $id = DB::table('plan_empleados')->where('documento', $dni)->value('id');
            $c = $id ? $this->contratoVigente($id) : null;
            if (!$c || $c->numero_cuenta) {
                continue;
            }
            DB::table('plan_contratos')->where('id', $c->id)->update(
                ['metodo_pago' => 'transferencia'] + $this->cuenta($principal, '') + ($secundaria ? $this->cuenta($secundaria, '_secundaria', 'banco_secundario') : [])
                + ['updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Datos de personas: no se deshacen (las columnas se quitan con la migración de planilla oficina)
    }

    private function contratoVigente(int $empleadoId): ?object
    {
        return DB::table('plan_contratos')->where('plan_empleado_id', $empleadoId)->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', '2026-01-01'))
            ->orderByDesc('fecha_inicio')->first();
    }

    /** "BCP $ 215-0728…" → banco BCP, moneda USD, número */
    private function cuenta(string $texto, string $sufijo, string $columnaBanco = 'banco'): array
    {
        $partes = preg_split('/\s+/', trim($texto));
        $banco = array_shift($partes);
        $moneda = 'PEN';
        if (($partes[0] ?? '') === '$') {
            $moneda = 'USD';
            array_shift($partes);
        }
        return [
            $columnaBanco => $banco,
            "moneda_cuenta{$sufijo}" => $moneda,
            "numero_cuenta{$sufijo}" => implode(' ', $partes),
        ];
    }
};
