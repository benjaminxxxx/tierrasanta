<?php

namespace App\Services\Campania\Registro;

use App\Models\CampoCampania;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\Auth;

/**
 * Operaciones primitivas sobre campos_campanias, siempre auditadas (tabla `auditorias`):
 * quién creó, editó o eliminó y qué cambió. Al eliminar se guarda la campaña completa en la auditoría.
 *
 * No valida reglas de negocio: eso lo hace CampaniaRegistroProceso antes de llamar aquí.
 */
class CampaniaRegistroCrud
{
    private const IGNORAR_EN_AUDITORIA = ['created_at', 'updated_at', 'usuario_modificador'];

    public function crear(array $data, ?string $observacion = null): CampoCampania
    {
        $campania = CampoCampania::create($this->preparar($data));

        AuditoriaServicio::registrar(
            modelo: CampoCampania::class,
            modeloId: $campania->id,
            accion: 'crear',
            despues: $campania->fresh()->getAttributes(),
            observacion: $observacion,
            camposIgnorados: self::IGNORAR_EN_AUDITORIA,
        );

        return $campania;
    }

    public function actualizar(CampoCampania $campania, array $data, ?string $observacion = null): CampoCampania
    {
        $antes = $campania->getAttributes();
        $campania->update($this->preparar($data));

        AuditoriaServicio::registrar(
            modelo: CampoCampania::class,
            modeloId: $campania->id,
            accion: 'editar',
            antes: $antes,
            despues: $campania->fresh()->getAttributes(),
            observacion: $observacion,
            camposIgnorados: self::IGNORAR_EN_AUDITORIA,
        );

        return $campania;
    }

    public function eliminar(CampoCampania $campania, ?string $observacion = null): void
    {
        $antes = $campania->getAttributes();
        $antes['eliminado_por'] = Auth::user()?->name;

        AuditoriaServicio::registrar(
            modelo: CampoCampania::class,
            modeloId: $campania->id,
            accion: 'eliminar',
            antes: $antes,
            observacion: $observacion ?? "Campaña {$campania->nombre_campania} del campo {$campania->campo}",
            camposIgnorados: ['updated_at'],
        );

        $campania->delete();
    }

    /** Solo columnas editables; textos vacíos del formulario → null; usuario que modifica. */
    private function preparar(array $data): array
    {
        $data = array_intersect_key($data, array_flip((new CampoCampania())->getFillable()));
        $data = array_map(fn($v) => $v === '' ? null : $v, $data);
        $data['usuario_modificador'] = Auth::id();
        return $data;
    }
}
