<?php

namespace App\Services\Caja\Movimiento;

use Illuminate\Support\Carbon;

/**
 * Reglas de caja sin base de datos: semana del mes, colores de fila y operaciones del importe.
 */
class CajaMovimientoReglas
{
    public const CONDICIONES = ['NEG' => 'NEG.', 'BLA' => 'BLA.'];

    public const MONEDA = 'PEN';

    /** Días mínimos para que la semana parcial del inicio o del fin del mes cuente como semana propia. */
    private const DIAS_SEMANA_PROPIA = 3;

    /**
     * Semana del mes como la lleva caja (SEM-1…SEM-5): semanas de lunes a domingo. Si el mes empieza en
     * sábado o domingo, esos días van con la primera semana completa; si termina con 1 o 2 días sueltos
     * (lunes 31, lunes 30 y martes 31), van con la semana anterior.
     */
    public static function semanaDelMes($fecha): int
    {
        $f = Carbon::parse($fecha)->startOfDay();
        $inicioMes = $f->copy()->startOfMonth();
        $finMes = $f->copy()->endOfMonth()->startOfDay();

        // Semanas (lunes) que tocan el mes, con cuántos días del mes tiene cada una
        $semanas = [];
        for ($d = $inicioMes->copy(); $d->lte($finMes); $d->addDay()) {
            $lunes = $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $semanas[$lunes] = ($semanas[$lunes] ?? 0) + 1;
        }

        $claves = array_keys($semanas);
        // Se unen las semanas cortas del inicio (con la siguiente) y del fin (con la anterior)
        $grupo = [];
        $numero = 0;
        foreach ($claves as $i => $lunes) {
            $esPrimeraCorta = $i === 0 && $semanas[$lunes] < self::DIAS_SEMANA_PROPIA && count($claves) > 1;
            $esUltimaCorta = $i === count($claves) - 1 && $i > 0 && $semanas[$lunes] < self::DIAS_SEMANA_PROPIA;
            if ($esUltimaCorta) {
                $grupo[$lunes] = $numero;
                continue;
            }
            if (!$esPrimeraCorta) {
                $numero++;
            }
            $grupo[$lunes] = max(1, $esPrimeraCorta ? 1 : $numero);
        }

        return $grupo[$f->copy()->startOfWeek(Carbon::MONDAY)->toDateString()] ?? 1;
    }

    /** "SALDO DE CAJA ANTERIOR": el saldo con que empieza el año. No es un ingreso, es el saldo anterior. */
    public static function esSaldoInicial(?string $clasificador1): bool
    {
        $c = mb_strtoupper(\Illuminate\Support\Str::ascii((string) $clasificador1));
        return str_contains($c, 'SALDO') && str_contains($c, 'ANTERIOR');
    }

    /** Huella de una fila para reconocerla entre el Excel y el sistema, o entre las dos cajas (sin mayúsculas, tildes ni espacios de más). */
    public static function huella(string $fecha, float $importe, ?string $beneficiario, ?string $descripcion): string
    {
        $n = fn($t) => trim(preg_replace('/\s+/', ' ', mb_strtoupper(\Illuminate\Support\Str::ascii((string) $t))));
        return $fecha . '|' . number_format($importe, 2, '.', '') . '|' . $n($beneficiario) . '|' . $n($descripcion);
    }

    public static function etiquetaSemana(?int $semana): string
    {
        return $semana ? "SEM-{$semana}" : '';
    }

    /**
     * Estilo con que se pinta la fila: el personalizado del movimiento manda; si no, el del clasificador;
     * si el subgrupo es ACTIVO, negrita.
     *
     * @return array{fondo: ?string, texto: ?string, negrita: bool}
     */
    public static function estilo(array $movimiento, ?array $clasificador): array
    {
        $fondo = $movimiento['color_fondo'] ?? null ?: ($clasificador['color_fondo'] ?? null);
        $texto = $movimiento['color_texto'] ?? null ?: ($clasificador['color_texto'] ?? null);
        $negrita = $movimiento['negrita'] ?? null;
        if ($negrita === null) {
            $negrita = (bool) ($clasificador['negrita'] ?? false)
                || mb_strtoupper(trim((string) ($movimiento['subgrupo_ng'] ?? ''))) === 'ACTIVO';
        }

        return ['fondo' => $fondo, 'texto' => $texto, 'negrita' => (bool) $negrita];
    }

    /**
     * Color por defecto de cada clasificador (lo que pidió administración):
     * ventas de cochinilla fondo #B4C6E7, ventas de naranja #FFD966, utilidades #FFFF00, pagos SUNAT letra
     * #FF9933, cuadrillas letra azul, fertilizantes/pesticidas/combustible letra verde, inversiones (activos) en
     * negrita.
     *
     * @return array{color_fondo: ?string, color_texto: ?string, negrita: bool}
     */
    public static function colorPorDefecto(string $clasificador1, string $clasificador2): array
    {
        $c1 = mb_strtoupper(trim($clasificador1));
        $c2 = mb_strtoupper(trim($clasificador2));
        $estilo = ['color_fondo' => null, 'color_texto' => null, 'negrita' => false];

        if (str_contains($c1, 'VENTA') && str_contains($c1, 'COCHINILLA')) {
            $estilo['color_fondo'] = '#B4C6E7';
            $estilo['negrita'] = true;
        } elseif (str_contains($c1, 'VENTA') && str_contains($c1, 'NARANJA')) {
            $estilo['color_fondo'] = '#FFD966';
            $estilo['negrita'] = true;
        } elseif (str_contains($c2, 'UTILIDAD')) {
            $estilo['color_fondo'] = '#FFFF00';
        } elseif (str_contains($c1, 'SUNAT')) {
            $estilo['color_texto'] = '#FF9933';
        } elseif (str_starts_with($c2, 'CUADRILLA')) {
            $estilo['color_texto'] = '#0070C0';
        } elseif (str_contains($c1, 'FERTILIZANTES Y PESTICIDAS') || str_contains($c2, 'COMBUSTIBLE')) {
            $estilo['color_texto'] = '#00B050';
        } elseif (str_contains($c2, 'INVERSION')) {
            $estilo['color_texto'] = '#000000';
            $estilo['negrita'] = true;
        }

        return $estilo;
    }

    /**
     * Evalúa una operación simple del importe ("-100-315", "=(-1260-1260)", "4105.18+66000-67033.69").
     * Solo números, + - * / y paréntesis; cualquier otra cosa es un error.
     */
    public static function evaluarOperacion(string $texto): float
    {
        $expr = str_replace([' ', ','], ['', ''], ltrim(trim($texto), '=+'));
        if ($expr === '' || !preg_match('/^[0-9.+\-*\/()]+$/', $expr)) {
            throw new \InvalidArgumentException("El importe \"{$texto}\" no es un número ni una operación válida.");
        }
        $pos = 0;
        $valor = self::expresion($expr, $pos);
        if ($pos !== strlen($expr)) {
            throw new \InvalidArgumentException("El importe \"{$texto}\" no es una operación válida.");
        }
        return round($valor, 2);
    }

    private static function expresion(string $s, int &$pos): float
    {
        $valor = self::termino($s, $pos);
        while ($pos < strlen($s) && in_array($s[$pos], ['+', '-'], true)) {
            $op = $s[$pos++];
            $derecha = self::termino($s, $pos);
            $valor = $op === '+' ? $valor + $derecha : $valor - $derecha;
        }
        return $valor;
    }

    private static function termino(string $s, int &$pos): float
    {
        $valor = self::factor($s, $pos);
        while ($pos < strlen($s) && in_array($s[$pos], ['*', '/'], true)) {
            $op = $s[$pos++];
            $derecha = self::factor($s, $pos);
            if ($op === '/' && abs($derecha) < 1e-12) {
                throw new \InvalidArgumentException('División entre cero en el importe.');
            }
            $valor = $op === '*' ? $valor * $derecha : $valor / $derecha;
        }
        return $valor;
    }

    private static function factor(string $s, int &$pos): float
    {
        if ($pos < strlen($s) && in_array($s[$pos], ['+', '-'], true)) {
            $signo = $s[$pos++] === '-' ? -1 : 1;
            return $signo * self::factor($s, $pos);
        }
        if ($pos < strlen($s) && $s[$pos] === '(') {
            $pos++;
            $valor = self::expresion($s, $pos);
            if ($pos >= strlen($s) || $s[$pos] !== ')') {
                throw new \InvalidArgumentException('Falta cerrar un paréntesis en el importe.');
            }
            $pos++;
            return $valor;
        }
        if (!preg_match('/\G\d+(\.\d+)?|\G\.\d+/', $s, $m, 0, $pos)) {
            throw new \InvalidArgumentException('El importe tiene una operación incompleta.');
        }
        $pos += strlen($m[0]);
        return (float) $m[0];
    }
}
