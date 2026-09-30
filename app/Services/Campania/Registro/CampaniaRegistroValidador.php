<?php

namespace App\Services\Campania\Registro;

use App\Models\CampoCampania;
use App\Services\Campania\Cobertura\CampaniaCoberturaConsulta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Reglas para crear o modificar una campaña. Los errores usan las claves del formulario (campania.*).
 */
class CampaniaRegistroValidador
{
    /**
     * @param int[] $ignorarIds campañas que no cuentan para el solapamiento (p. ej. la anterior, si en la misma
     *                          operación se cierra el día antes)
     * @throws ValidationException
     */
    public function validar(array $data, ?int $campaniaId = null, array $ignorarIds = []): void
    {
        $errores = [];
        $campo = $data['campo'] ?? null;
        $nombre = trim((string) ($data['nombre_campania'] ?? ''));
        $inicio = $data['fecha_inicio'] ?? null;
        $fin = ($data['fecha_fin'] ?? null) ?: null;

        if (!$campo) {
            $errores['campania.campo'] = 'Debe seleccionar un campo.';
        }
        if ($nombre === '') {
            $errores['campania.nombre_campania'] = 'El nombre de la campaña es obligatorio.';
        } elseif ($campo && $this->nombreEnUso($campo, $nombre, $campaniaId)) {
            $errores['campania.nombre_campania'] = "Ya existe una campaña {$nombre} en el campo {$campo}.";
        }
        if (!$inicio) {
            $errores['campania.fecha_inicio'] = 'La fecha de inicio es obligatoria.';
        }
        if ($inicio && $fin && $fin < $inicio) {
            $errores['campania.fecha_fin'] = 'La fecha de cierre debe ser igual o posterior a la de inicio.';
        }

        if (!$errores && $campo) {
            $choques = $this->solapamientos($campo, $inicio, $fin, array_filter([$campaniaId, ...$ignorarIds]));
            if ($choques->isNotEmpty()) {
                $errores['campania.fecha_inicio'] = 'Las fechas se cruzan con: ' . $choques
                    ->map(fn($c) => "{$c->nombre_campania} (" . formatear_fecha($c->fecha_inicio) . ' – '
                        . ($c->fecha_fin ? formatear_fecha($c->fecha_fin) : 'abierta') . ')')
                    ->implode(', ') . '.';
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Los días entre la campaña anterior y esta (y entre esta y la siguiente, si se indica cierre) no pueden
     * tener actividades: toda labor debe pertenecer a alguna campaña.
     *
     * @throws ValidationException
     */
    public function validarCobertura(string $campo, string $inicio, ?string $fin, ?int $campaniaId = null): void
    {
        $huecos = app(CampaniaCoberturaConsulta::class)->huecos($campo, $inicio, $fin, $campaniaId);
        $errores = [];

        foreach (['antes' => 'campania.fecha_inicio', 'despues' => 'campania.fecha_fin'] as $lado => $clave) {
            $hueco = $huecos[$lado];
            if (!$hueco || !$hueco['actividades']) {
                continue;
            }
            $dias = array_keys($hueco['actividades']);
            $vecina = $hueco['vecina'];
            $errores[$clave] = count($dias) . ' día(s) con actividades quedarían sin campaña entre '
                . ($lado === 'antes' ? "el cierre de {$vecina->nombre_campania} y el inicio" : "el cierre y el inicio de {$vecina->nombre_campania}")
                . ': ' . implode(', ', array_map('formatear_fecha', array_slice($dias, 0, 5)))
                . (count($dias) > 5 ? '…' : '') . '.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Campañas del campo cuyo rango se cruza con [inicio, fin]. Un fin null es una campaña abierta (sin límite).
     *
     * @return Collection<int, CampoCampania>
     */
    public function solapamientos(string $campo, string $inicio, ?string $fin, array $excluirIds = []): Collection
    {
        $inicio = Carbon::parse($inicio)->toDateString();
        $fin = $fin ? Carbon::parse($fin)->toDateString() : null;

        return CampoCampania::where('campo', $campo)
            ->when($excluirIds, fn($q) => $q->whereNotIn('id', $excluirIds))
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $inicio))
            ->when($fin, fn($q) => $q->whereDate('fecha_inicio', '<=', $fin))
            ->orderBy('fecha_inicio')
            ->get();
    }

    private function nombreEnUso(string $campo, string $nombre, ?int $campaniaId): bool
    {
        return CampoCampania::where('campo', $campo)
            ->whereRaw('UPPER(nombre_campania) = ?', [mb_strtoupper($nombre)])
            ->when($campaniaId, fn($q) => $q->where('id', '!=', $campaniaId))
            ->exists();
    }
}
