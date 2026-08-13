<?php

namespace App\Domain\DerechoHabiente;

use App\Models\DerechoHabiente;
use App\Models\EmpleadoDerechoHabiente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class DerechoHabienteService
{
    /**
     * Crea o actualiza la persona (derecho_habiente) y sincroniza su lista de vínculos.
     * Usado por el Form (edición desde el punto de vista del derecho-habiente).
     *
     * @param array $datosPersona ['nombres','documento','tipo_documento','fecha_nacimiento','tipo','discapacidad_severa']
     * @param array $vinculos cada fila: ['id'?, 'empleado_id', 'rol', 'fecha_vigencia_asignacion', 'fecha_inicio_estudios'?, 'fecha_fin_estudios'?, 'activo']
     */
    public function guardar(?int $derechoHabienteId, array $datosPersona, array $vinculos): DerechoHabiente
    {
        return DB::transaction(function () use ($derechoHabienteId, $datosPersona, $vinculos) {
            $persona = $derechoHabienteId
                ? DerechoHabiente::findOrFail($derechoHabienteId)
                : new DerechoHabiente();

            if ($persona->documento !== ($datosPersona['documento'] ?? null)) {
                $duplicado = DerechoHabiente::where('documento', $datosPersona['documento'])
                    ->when($persona->id, fn($q) => $q->where('id', '!=', $persona->id))
                    ->first();

                if ($duplicado) {
                    throw new \RuntimeException(
                        "El documento {$datosPersona['documento']} ya pertenece a {$duplicado->nombres}. Use ese registro para vincular en vez de crear uno nuevo."
                    );
                }
            }
           
            $persona->fill($datosPersona);
            $persona->actualizado_por = auth()->id();
            if (!$persona->exists) {
                $persona->creado_por = auth()->id();
            }
            $persona->save();

            foreach ($vinculos as $fila) {
                if (empty($fila['activo'])) {
                    if (!empty($fila['id'])) {
                        EmpleadoDerechoHabiente::where('id', $fila['id'])->delete();
                    }
                    continue;
                }

                $vinculo = !empty($fila['id'])
                    ? EmpleadoDerechoHabiente::findOrFail($fila['id'])
                    : new EmpleadoDerechoHabiente();

                $this->validarUnicidadRol($persona->id, $fila['rol'], $vinculo->id ?: null);

                $vinculo->fill([
                    'derecho_habiente_id' => $persona->id,
                    'empleado_id' => $fila['empleado_id'],
                    'rol' => $fila['rol'],
                    'mes_vigencia' => $persona->tipo === 'hijo' ? $fila['mes_vigencia'] : null,
                    'anio_vigencia' => $persona->tipo === 'hijo' ? $fila['anio_vigencia'] : null,
                    'activo' => $fila['activo'] ?? true,
                ]);
                $vinculo->actualizado_por = auth()->id();
                if (!$vinculo->exists) {
                    $vinculo->creado_por = auth()->id();
                }
                $vinculo->save();
            }

            return $persona;
        });
    }

    /**
     * Alta rápida desde el wizard: busca por documento (find-or-create de la persona)
     * y crea el vínculo con el empleado. Cada llamada es atómica.
     */
    // DerechoHabienteService::agregarVinculo() — firma y cuerpo actualizados
    public function agregarVinculo(int $empleadoId, array $datosPersona, string $rol, array $vigencia): array
    {
        return DB::transaction(function () use ($empleadoId, $datosPersona, $rol, $vigencia) {
            $persona = DerechoHabiente::where('documento', $datosPersona['documento'])->first();
            $yaExistia = (bool) $persona;

            if (!$persona) {
                $persona = DerechoHabiente::create([...$datosPersona, 'creado_por' => auth()->id(), 'actualizado_por' => auth()->id()]);
            }

            if (EmpleadoDerechoHabiente::where('derecho_habiente_id', $persona->id)->where('empleado_id', $empleadoId)->exists()) {
                throw new \RuntimeException("{$persona->nombres} ya está vinculado a este trabajador.");
            }

            $this->validarUnicidadRol($persona->id, $rol, null);

            $vinculo = EmpleadoDerechoHabiente::create([
                'derecho_habiente_id' => $persona->id,
                'empleado_id' => $empleadoId,
                'rol' => $rol,
                'mes_vigencia' => $persona->tipo === 'hijo' ? $vigencia['mes_vigencia'] : null,
                'anio_vigencia' => $persona->tipo === 'hijo' ? $vigencia['anio_vigencia'] : null,
                'fecha_inicio_estudios' => $vigencia['fecha_inicio_estudios'] ?? null,
                'fecha_fin_estudios' => $vigencia['fecha_fin_estudios'] ?? null,
                'activo' => true,
                'creado_por' => auth()->id(),
                'actualizado_por' => auth()->id(),
            ]);

            return ['persona' => $persona, 'vinculo' => $vinculo, 'ya_existia' => $yaExistia];
        });
    }

    public function eliminarVinculo(int $vinculoId): void
    {
        EmpleadoDerechoHabiente::findOrFail($vinculoId)->delete();
    }

    /** padre y madre son roles únicos por derecho-habiente; conyuge/tutor no lo son (puede haber varios tutores). */
    private function validarUnicidadRol(int $derechoHabienteId, string $rol, ?int $vinculoIdActual): void
    {
        if (!in_array($rol, ['padre', 'madre'])) {
            return;
        }

        $existe = EmpleadoDerechoHabiente::where('derecho_habiente_id', $derechoHabienteId)
            ->where('rol', $rol)
            ->when($vinculoIdActual, fn($q) => $q->where('id', '!=', $vinculoIdActual))
            ->exists();

        if ($existe) {
            throw new \RuntimeException("Este derecho-habiente ya tiene un(a) {$rol} registrado(a).");
        }
    }
}