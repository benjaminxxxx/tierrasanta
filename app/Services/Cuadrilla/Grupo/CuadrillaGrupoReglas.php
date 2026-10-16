<?php

namespace App\Services\Cuadrilla\Grupo;

use App\Models\Configuracion;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Grupos de cuadrilla: parámetros (meses sin tramo para considerar inactivo un grupo) y el código sugerido.
 */
class CuadrillaGrupoReglas
{
    public const CONFIG_MESES_SIN_TRAMO = 'cuadrilla_grupo_meses_sin_tramo';
    public const MESES_SIN_TRAMO = 3;

    /** Prefijo del código según la modalidad de pago: CM (mensual), CQ (quincenal), CS (semanal). */
    public const PREFIJOS = ['mensual' => 'CM', 'quincenal' => 'CQ', 'semanal' => 'CS'];

    public static function mesesSinTramo(): int
    {
        $valor = (int) Configuracion::find(self::CONFIG_MESES_SIN_TRAMO)?->valor;
        return $valor > 0 ? $valor : self::MESES_SIN_TRAMO;
    }

    public static function guardarMesesSinTramo($meses): void
    {
        $meses = (int) $meses;
        if ($meses < 1) {
            throw ValidationException::withMessages(['meses_sin_tramo' => 'Indica al menos 1 mes.']);
        }
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_MESES_SIN_TRAMO], [
            'valor' => (string) $meses,
            'descripcion' => 'Meses sin participar en un tramo para considerar inactivo un grupo de cuadrilla',
        ]);
    }

    /**
     * Código sugerido: prefijo de la modalidad + última palabra del nombre ("CUAD. SEMANAL LA JOYA" semanal → CSJOYA).
     * Si ya existe se le agrega 2, 3… ("CSJOYA2").
     *
     * @param string[] $existentes códigos ya usados (incluidos los eliminados)
     */
    public static function sugerirCodigo(?string $nombre, ?string $modalidad, array $existentes): string
    {
        $prefijo = self::PREFIJOS[$modalidad] ?? 'CS';
        $palabras = preg_split('/[^A-Z0-9]+/', mb_strtoupper(Str::ascii((string) $nombre)), -1, PREG_SPLIT_NO_EMPTY);
        $ultima = '';
        // La última palabra con letras (se saltan números sueltos: "GRUPO VI 2" → VI)
        while ($palabras) {
            $p = array_pop($palabras);
            if (preg_match('/[A-Z]/', $p)) {
                $ultima = $p;
                break;
            }
        }
        $base = mb_substr($prefijo . $ultima, 0, 27);

        $usados = array_flip(array_map('mb_strtoupper', $existentes));
        $codigo = $base;
        for ($n = 2; isset($usados[$codigo]); $n++) {
            $codigo = $base . $n;
        }
        return $codigo;
    }
}
