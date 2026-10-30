<?php

namespace App\Services\Cuadrilla\Bono;

use App\Models\ActividadMetodo;

/**
 * Reglas de los métodos de bono. Un solo lugar para saber si un método descuenta horas del jornal (destajo antiguo) o
 * respeta el jornal (destajo con jornal: el bono es solo lo que el destajo supera al jornal de esas horas).
 *
 * El cálculo del bono en pantalla está en la vista de bonificaciones (Alpine); estas fórmulas son las mismas y las usan
 * las pruebas y los reportes.
 */
class CuadrillaBonoReglas
{
    /** Destajo antiguo: sin estándar y sin "respeta el jornal" → esas horas no cobran jornal, se pagan con el bono. */
    public static function descuentaJornal(?ActividadMetodo $metodo): bool
    {
        return $metodo !== null && $metodo->estandar === null && !$metodo->respeta_jornal;
    }

    /** Monto del destajo con tramos progresivos (el último tramo se aplica al excedente). */
    public static function montoTramos(float $cantidad, array $tramos): float
    {
        $tramos = self::ordenar($tramos);
        if (!$tramos || $cantidad <= 0) {
            return 0.0;
        }
        $monto = 0.0;
        $restante = $cantidad;
        foreach ($tramos as [$hasta, $tarifa]) {
            if ($restante <= 0) {
                break;
            }
            $tomado = min($restante, $hasta);
            $monto += $tomado * $tarifa;
            $restante -= $tomado;
        }
        if ($restante > 0) {
            $monto += $restante * end($tramos)[1];
        }
        return $monto;
    }

    /** Cantidad mínima para que el destajo iguale un monto (inversa de montoTramos). null si no se puede alcanzar. */
    public static function cantidadParaMonto(float $monto, array $tramos): ?float
    {
        $tramos = self::ordenar($tramos);
        if ($monto <= 0) {
            return 0.0;
        }
        $cantidad = 0.0;
        $falta = $monto;
        foreach ($tramos as [$hasta, $tarifa]) {
            if ($tarifa <= 0) {
                $cantidad += $hasta;
                continue;
            }
            if ($hasta * $tarifa >= $falta) {
                return $cantidad + $falta / $tarifa;
            }
            $cantidad += $hasta;
            $falta -= $hasta * $tarifa;
        }
        $ultima = $tramos ? end($tramos)[1] : 0;
        return $ultima > 0 ? $cantidad + $falta / $ultima : null;
    }

    /**
     * Bono del destajo que respeta el jornal: lo que el destajo supera al jornal de esas horas (mínimo 0).
     * @return array{bono: float, jornal_horas: float, destajo: float, minimo: ?float}
     */
    public static function bonoRespetaJornal(float $cantidad, float $horas, float $jornalDia, array $tramos): array
    {
        $jornalHoras = $jornalDia / 8 * $horas;
        $destajo = self::montoTramos($cantidad, $tramos);
        return [
            'bono' => round(max(0, $destajo - $jornalHoras), 2),
            'jornal_horas' => round($jornalHoras, 2),
            'destajo' => round($destajo, 2),
            'minimo' => self::cantidadParaMonto($jornalHoras, $tramos),
        ];
    }

    /** Tramos como [[hasta, tarifa], ...] ordenados por "hasta"; acepta arrays o modelos con hasta/monto. */
    private static function ordenar(array $tramos): array
    {
        $lista = [];
        foreach ($tramos as $t) {
            $t = (array) (is_object($t) && method_exists($t, 'toArray') ? $t->toArray() : $t);
            if (($t['monto'] ?? '') === '' && ($t['hasta'] ?? '') === '') {
                continue;
            }
            $lista[] = [(float) ($t['hasta'] ?? 0), (float) ($t['monto'] ?? 0)];
        }
        usort($lista, fn($a, $b) => $a[0] <=> $b[0]);
        return $lista;
    }
}
