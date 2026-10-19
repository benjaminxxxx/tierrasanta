<?php

namespace App\Services\Cochinilla\Recuperacion;

use App\Models\Configuracion;
use Illuminate\Validation\ValidationException;

/**
 * Recuperación de cochinilla de infestadores (reglas del ingeniero, 19/10/2026):
 * - Un campo se infesta con mamá de 1 a 3 campos. La mano de obra de infestar y de recuperar es del campo infestado
 *   (campaña vigente); los kilos recuperados son de los campos de donde vino la mamá.
 * - En el camino se pierde peso (cochinilla que muere, vuelta…): de 120 kg puestos se recuperan 80. Por eso no se
 *   impone un reparto: se sugiere por el % de kg de mamá que aportó cada campo y quien registra lo corrige.
 */
class CochinillaRecuperacionReglas
{
    public const CONFIG_DIAS_ALERTA = 'cochinilla_recuperacion_dias_alerta';
    /** Días desde la infestación sin dar por recuperada la tanda para avisar (valor por defecto). */
    public const DIAS_ALERTA = 30;
    /** Tandas que se muestran: las infestadas en los últimos días. */
    public const DIAS_VENTANA = 365;

    /**
     * Factor fresco → seco: la mamá se pesa fresca y vuelve casi seca. Ingeniero: entre 4.8 y 5 (1,000 kg de mamá
     * dan unos 200 kg secos). Sirve para ver si lo recuperado cuadra con lo puesto.
     */
    public const CONFIG_FACTOR_SECO = 'cochinilla_factor_fresco_seco';
    public const FACTOR_SECO = 5.0;

    public static function factorSeco(): float
    {
        $valor = (float) Configuracion::find(self::CONFIG_FACTOR_SECO)?->valor;
        return $valor > 0 ? $valor : self::FACTOR_SECO;
    }

    public static function guardarFactorSeco($factor): void
    {
        $factor = (float) str_replace(',', '.', (string) $factor);
        if ($factor < 1 || $factor > 20) {
            throw ValidationException::withMessages(['factor_seco' => 'El factor debe estar entre 1 y 20 (normalmente 4.8 a 5).']);
        }
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_FACTOR_SECO], [
            'valor' => (string) $factor,
            'descripcion' => 'Factor fresco a seco de la cochinilla mamá (kg frescos / kg secos recuperados)',
        ]);
    }

    public static function diasAlerta(): int
    {
        $valor = (int) Configuracion::find(self::CONFIG_DIAS_ALERTA)?->valor;
        return $valor > 0 ? $valor : self::DIAS_ALERTA;
    }

    public static function guardarDiasAlerta($dias): void
    {
        $dias = (int) $dias;
        if ($dias < 1 || $dias > 365) {
            throw ValidationException::withMessages(['dias_alerta' => 'Indica entre 1 y 365 días.']);
        }
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_DIAS_ALERTA], [
            'valor' => (string) $dias,
            'descripcion' => 'Días desde la infestación sin dar por recuperados sus infestadores para avisar',
        ]);
    }

    /**
     * Reparto sugerido de $kgTotal entre orígenes según sus kg de mamá. Redondeado a 2 decimales; el último origen
     * absorbe el redondeo para que la suma sea exacta.
     *
     * @param array<string, float> $kgMadres campo de origen => kg de mamá aportados
     * @return array<string, array{kg_madres: float, porcentaje: float, kg: float}>
     */
    public static function repartoSugerido(float $kgTotal, array $kgMadres): array
    {
        $totalMadres = array_sum($kgMadres);
        $reparto = [];
        $asignado = 0.0;
        $campos = array_keys($kgMadres);
        foreach ($campos as $i => $campo) {
            $porcentaje = $totalMadres > 0 ? $kgMadres[$campo] / $totalMadres : 1 / max(1, count($campos));
            $kg = $i === count($campos) - 1 ? round($kgTotal - $asignado, 2) : round($kgTotal * $porcentaje, 2);
            $asignado += $kg;
            $reparto[$campo] = ['kg_madres' => (float) $kgMadres[$campo], 'porcentaje' => round($porcentaje * 100, 4), 'kg' => $kg];
        }
        return $reparto;
    }
}
