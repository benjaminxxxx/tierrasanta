<?php

namespace App\Services\Cuadrilla\Persona;

use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use Illuminate\Support\Collection;

/**
 * ¿Este cuadrillero es también un trabajador de planilla? Los cuadrilleros casi nunca tienen DNI, así que se cruza
 * por DNI cuando lo hay y, si no, por nombre: todas las palabras del nombre del cuadrillero (de 3 letras o más, al
 * menos dos) están en el nombre completo del empleado ("RICHARD ALVAREZ" ↔ "ALVAREZ CHAVEZ, RICHARD").
 * Es una sospecha (puede haber homónimos): se usa para avisar, nunca para bloquear.
 */
class CuadrillaPersonaConsulta
{
    /** @var Collection|null empleados [id, nombre en mayúsculas, documento] */
    private ?Collection $empleados = null;
    private array $cache = [];

    /** @return array{id:int, nombre:string, documento:?string}|null */
    public function empleadoDePlanilla(Cuadrillero $cuadrillero): ?array
    {
        if (array_key_exists($cuadrillero->id, $this->cache)) {
            return $this->cache[$cuadrillero->id];
        }
        $this->empleados ??= PlanEmpleado::get()->map(fn($e) => [
            'id' => $e->id, 'nombre' => $e->nombre_completo, 'mayus' => mb_strtoupper((string) $e->nombre_completo), 'documento' => $e->documento,
        ]);

        $dni = trim((string) $cuadrillero->dni);
        $dniReal = strlen(ltrim($dni, '0')) >= 6 ? $dni : null;
        $palabras = array_values(array_filter(preg_split('/[\s,]+/', mb_strtoupper(trim((string) $cuadrillero->nombres))) ?: [], fn($w) => mb_strlen($w) >= 3));

        $e = $this->empleados->first(fn($e) => $dniReal && $e['documento'] === $dniReal)
            ?? (count($palabras) >= 2 ? $this->empleados->first(fn($e) => collect($palabras)->every(fn($w) => str_contains($e['mayus'], $w))) : null);

        return $this->cache[$cuadrillero->id] = $e ? ['id' => $e['id'], 'nombre' => $e['nombre'], 'documento' => $e['documento']] : null;
    }

    /**
     * Igual, pero solo si ese empleado tiene contrato de planilla agraria en la fecha: un homónimo o una ficha antigua
     * sin contrato (p. ej. "MAMANI IDME, ANDRES FORTUNATO" para el cuadrillero ANDRES MAMANI) no cuenta.
     */
    public function empleadoConContrato(Cuadrillero $cuadrillero, string $fecha): ?array
    {
        $e = $this->empleadoDePlanilla($cuadrillero);
        if (!$e) {
            return null;
        }
        $f = \Illuminate\Support\Carbon::parse($fecha)->toDateString();
        $tiene = \App\Models\PlanContrato::where('plan_empleado_id', $e['id'])->where('tipo_planilla', 'agraria')
            ->whereDate('fecha_inicio', '<=', $f)->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $f))->exists();
        return $tiene ? $e : null;
    }
}
