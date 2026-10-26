<?php

namespace App\Services\Campo\Bloqueo;

use App\Models\Campo;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Validation\ValidationException;

/**
 * Bloquear / desbloquear un campo. Un campo bloqueado ya no se usa en la empresa nueva pero conserva su historia:
 * no se borra, solo deja de aparecer en selectores y listas de trabajo. También se cambia desde el Excel de recursos.
 */
class CampoBloqueoProceso
{
    public function cambiar(string $nombre, bool $bloquear): Campo
    {
        $campo = Campo::find($nombre);
        if (!$campo) {
            throw ValidationException::withMessages(['campo' => "El campo {$nombre} no existe."]);
        }
        if ($bloquear && $campo->campanias()->whereNull('fecha_fin')->exists()) {
            throw ValidationException::withMessages(['campo' => "El campo {$nombre} tiene una campaña abierta: ciérrala antes de bloquearlo."]);
        }
        if ((bool) $campo->bloqueado === $bloquear) {
            return $campo;
        }

        $campo->update(['bloqueado' => $bloquear]);
        AuditoriaServicio::registrar(Campo::class, $campo->nombre, 'editar', ['bloqueado' => !$bloquear], ['bloqueado' => $bloquear],
            $bloquear ? 'Campo bloqueado (ya no se usa)' : 'Campo desbloqueado');

        return $campo;
    }
}
