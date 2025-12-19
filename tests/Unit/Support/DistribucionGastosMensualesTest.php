<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Support\DistribucionGastosMensuales;

class DistribucionGastosMensualesTest extends TestCase
{
    #[Test]
    public function distribuye_gastos_proporcionalmente_por_dias_activos()
    {
        $anio = 2025;
        $mes  = 1;

        $gastos = [
            'mano_obra_indirecta' => 1000.00,
            'servicios_fundo'     => 800.00,
            'costo_administrativo'=> 600.00,
            'costo_financiero'    => 400.00,
            'gastos_oficina'      => 200.00,
            'costo_terreno'       => 300.00,
            'depreciaciones'      => 500.00,
        ];

        $campanias = [
            [
                'campania_id'  => 1,
                'fecha_inicio' => '2025-01-01',
                'fecha_fin'    => '2025-01-31',
            ],
            [
                'campania_id'  => 2,
                'fecha_inicio' => '2025-01-15',
                'fecha_fin'    => '2025-03-10',
            ],
        ];

        $resultado = DistribucionGastosMensuales::calcular(
            $anio,
            $mes,
            $gastos,
            $campanias
        );

        $this->assertCount(2, $resultado);

        $this->assertEquals(31, $resultado[0]['dias_activos']);
        $this->assertEquals(17, $resultado[1]['dias_activos']);

        $this->assertEqualsWithDelta(31 / 48, $resultado[0]['porcentaje'], 0.00001);
        $this->assertEqualsWithDelta(17 / 48, $resultado[1]['porcentaje'], 0.00001);

        $this->assertEqualsWithDelta(
            1000 * (31 / 48),
            $resultado[0]['monto_mano_obra_indirecta'],
            0.01
        );

        $total = array_sum(
            array_column($resultado, 'monto_mano_obra_indirecta')
        );

        $this->assertEquals(1000.00, round($total, 2));
    }
}
