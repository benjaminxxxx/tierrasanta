<x-dialog-modal wire:model.live="show" maxWidth="full">
    <x-slot name="title">
        Registro de derecho-habientes y sus vínculos con empleados
    </x-slot>

    <x-slot name="content">
        {{-- PASO 1: seleccionar empleado --}}
        @if ($paso === 'seleccionar')
            <div class="w-full">
                <x-label value="Buscar empleado" />
                <x-select-dropdown wire:model="empleadoId" source="getEmpleados"
                    placeholder="Escriba nombre, apellido o DNI..." />
            </div>
        @endif

        {{-- PASO 2: gestionar (lista existente + agregar nuevos) --}}
        @if ($paso === 'gestionar')
            <div class="flex items-center justify-between mb-4">
                <x-subtitle>Empleado: {{ $empleadoNombre }}</x-subtitle>
                <x-button size="sm" variant="secondary" wire:click="cambiarEmpleado">
                    <i class="fa fa-sync"></i> Cambiar empleado
                </x-button>
            </div>

            <x-h3 class="mb-3">Derecho-habientes registrados</x-h3>

            @if (empty($vinculosExistentes))
                <x-callout variant="warning" heading="Aún no tiene derecho-habientes registrados." class="mb-6" />
            @else
                <x-table class="mb-6">
                    <x-slot name="thead">
                        <x-tr>
                            <x-th value="Nombres" />
                            <x-th value="Documento" class="text-center" />
                            <x-th value="Tipo" class="text-center" />
                            <x-th value="Rol" class="text-center" />
                            <x-th value="Edad" class="text-center" />
                            <x-th value="Acciones" class="text-center" />
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($vinculosExistentes as $v)
                            <x-tr>
                                <x-td value="{{ $v['nombres'] }}" />
                                <x-td value="{{ $v['documento'] }}" class="text-center" />
                                <x-td value="{{ ucfirst($v['tipo']) }}" class="text-center" />
                                <x-td value="{{ ucfirst($v['rol']) }}" class="text-center" />
                                <x-td value="{{ $v['edad'] }}" class="text-center" />
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-button size="sm" wire:click="editarVinculo({{ $v['id'] }})">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-button size="sm" variant="danger" wire:click="confirmarEliminacion({{ $v['id'] }})">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    </div>
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
            @endif

            <x-h3 class="mb-3">Agregar nuevo(s)</x-h3>

            <x-flex>
                <div>
                    <x-label value="Rol de {{ $empleadoNombre }}" />
                    <x-select wire:model="rolEmpleado">
                        <option value="padre">Padre</option>
                        <option value="madre">Madre</option>
                        <option value="tutor">Tutor</option>
                    </x-select>
                </div>

                <x-button wire:click="agregarDerechoHabiente" variant="primary" size="sm">
                    <i class="fa fa-plus"></i> Agregar derecho-habiente
                </x-button>
            </x-flex>

        @endif
    </x-slot>

    <x-slot name="footer">
        @if ($paso === 'seleccionar')
            <x-button wire:click="cerrar" variant="secondary">Cancelar</x-button>
        @endif
        @if ($paso === 'gestionar')
            <x-button wire:click="cerrar" variant="secondary">Cerrar</x-button>
        @endif
    </x-slot>
</x-dialog-modal>