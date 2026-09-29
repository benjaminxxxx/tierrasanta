<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsKardexReporte extends Model
{
    use HasFactory;
    protected $table = 'ins_kardex_reportes';
    protected $fillable = [
        'nombre',
        'anio',
        'estado',
        'tipo_kardex',
        'grupos_operativos',
        'file',
        'generado_at',
    ];

    protected $casts = [
        'grupos_operativos' => 'array',
        'generado_at' => 'datetime',
    ];

    /** Orden habitual de los grupos en el reporte (los demás van después, alfabéticamente). */
    public const ORDEN_GRUPOS = ['fertilizante', 'pesticida', 'combustible'];

    /** Tono pastel por posición del grupo en el reporte: 1.º, 2.º, 3.º... */
    public const COLORES_GRUPO = ['EDEDED', 'FFF2CC', 'E2EFDA', 'DDEBF7', 'FCE4D6'];

    /**
     * Grupos operativos que existen en las categorías de insumos, en el orden del reporte.
     */
    public static function gruposDisponibles(): array
    {
        return self::ordenarGrupos(
            InsCategoria::whereNotNull('grupo_operativo')->distinct()->pluck('grupo_operativo')->all()
        );
    }

    public static function ordenarGrupos(array $grupos): array
    {
        $grupos = array_values(array_unique($grupos));
        usort($grupos, function ($a, $b) {
            $pa = array_search($a, self::ORDEN_GRUPOS, true);
            $pb = array_search($b, self::ORDEN_GRUPOS, true);
            $pa = $pa === false ? PHP_INT_MAX : $pa;
            $pb = $pb === false ? PHP_INT_MAX : $pb;
            return $pa <=> $pb ?: strcmp($a, $b);
        });
        return $grupos;
    }

    /** Grupos de este reporte, ordenados. */
    public function getGruposOrdenadosAttribute(): array
    {
        return self::ordenarGrupos($this->grupos_operativos ?? []);
    }

    /** Color (hex sin #) del grupo según su posición en este reporte. */
    public function colorGrupo(?string $grupo): string
    {
        $pos = array_search($grupo, $this->grupos_ordenados, true);
        return $pos === false ? 'FFFFFF' : self::COLORES_GRUPO[$pos % count(self::COLORES_GRUPO)];
    }

    /**
     * Nombre automático del reporte, ej. "KARDEX 2026 NEGRO - FERTILIZANTES Y PESTICIDAS".
     * La vista del formulario arma el mismo texto con Alpine (mantener ambos iguales).
     */
    public static function nombreAutomatico($anio, ?string $tipo, array $grupos): string
    {
        $partes = array_map(fn($g) => self::etiquetaGrupo($g), self::ordenarGrupos($grupos));
        $ultimo = array_pop($partes);
        $textoGrupos = $partes ? implode(', ', $partes) . ' Y ' . $ultimo : ($ultimo ?? '');

        return trim(mb_strtoupper(trim("KARDEX {$anio} {$tipo}") . ($textoGrupos ? " - {$textoGrupos}" : '')));
    }

    public static function etiquetaGrupo(?string $grupo): string
    {
        return $grupo ? mb_strtoupper($grupo) . 'S' : 'SIN GRUPO';
    }

    public function categorias()
    {
        return $this->hasMany(InsKardexReporteCategoria::class, 'reporte_id');
    }
    public function detalles()
    {
        return $this->hasMany(InsKardexReporteDetalle::class, 'reporte_id')->orderBy('id');
    }
}
