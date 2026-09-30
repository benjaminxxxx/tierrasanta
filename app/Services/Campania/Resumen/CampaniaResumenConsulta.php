<?php

namespace App\Services\Campania\Resumen;

use App\Models\CampoCampania;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado de /campania/resumen (solo lectura). Vigente = campaña sin fecha de cierre.
 */
class CampaniaResumenConsulta
{
    public const ESTADO_VIGENTES = 'vigentes';
    public const ESTADO_CERRADAS = 'cerradas';
    public const ESTADO_TODAS = 'todas';

    public function __construct(private CampaniaCosechaConsulta $cosecha)
    {
    }

    /**
     * @param array{campo?: ?string, campania?: ?string, estado?: ?string, por_cerrar?: bool} $filtros
     * @return array{campanias: LengthAwarePaginator, cosecha: array<int, array>}
     */
    public function listar(array $filtros, int $porPagina = 20): array
    {
        $query = $this->query($filtros);

        // "Por cerrar" depende del estado de cosecha (no es una columna): se calcula sobre las vigentes filtradas
        if (!empty($filtros['por_cerrar'])) {
            $candidatas = (clone $query)->whereNull('fecha_fin')->get();
            $estados = $this->cosecha->estados($candidatas);
            $ids = $candidatas->filter(fn($c) => $estados[$c->id]['recomendar_cierre'] ?? null)->pluck('id');
            $query->whereIn('id', $ids);
        }

        $campanias = $query
            ->orderBy('campo')
            ->orderByDesc('fecha_inicio')
            ->paginate($porPagina);

        return [
            'campanias' => $campanias,
            'cosecha' => $this->cosecha->estados($campanias->getCollection()),
        ];
    }

    /** Registros para el Excel, con los mismos filtros de la pantalla. */
    public function todas(array $filtros)
    {
        return $this->query($filtros)->orderByDesc('nombre_campania')->orderBy('campo')->get();
    }

    /** @return array{vigentes: int, cerradas: int, por_cerrar: int} */
    public function totales(?string $campo = null): array
    {
        $base = CampoCampania::query()->when($campo, fn($q) => $q->where('campo', $campo));
        $vigentes = (clone $base)->whereNull('fecha_fin')->get();
        $estados = $this->cosecha->estados($vigentes);

        return [
            'vigentes' => $vigentes->count(),
            'cerradas' => (clone $base)->whereNotNull('fecha_fin')->count(),
            'por_cerrar' => collect($estados)->filter(fn($e) => $e['recomendar_cierre'])->count(),
        ];
    }

    /** @return array<string, string> nombre => nombre, de las campañas del campo */
    public function nombresPorCampo(string $campo): array
    {
        return CampoCampania::where('campo', $campo)
            ->orderByDesc('fecha_inicio')
            ->pluck('nombre_campania', 'nombre_campania')
            ->toArray();
    }

    private function query(array $filtros): Builder
    {
        $estado = $filtros['estado'] ?? self::ESTADO_VIGENTES;

        return CampoCampania::query()
            ->with('campo_model')
            ->when($filtros['campo'] ?? null, fn($q, $campo) => $q->where('campo', $campo))
            ->when($filtros['campania'] ?? null, fn($q, $nombre) => $q->where('nombre_campania', $nombre))
            ->when($estado === self::ESTADO_VIGENTES, fn($q) => $q->whereNull('fecha_fin'))
            ->when($estado === self::ESTADO_CERRADAS, fn($q) => $q->whereNotNull('fecha_fin'));
    }
}
