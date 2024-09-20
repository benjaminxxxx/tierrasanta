<div>
    <x-loading wire:loading wire:target="file" />
    <x-loading wire:loading wire:target="export" />
    <div class="block md:flex items-center gap-5">
        <div x-data="{ openFileDialog() { $refs.fileInput.click() } }">
            <!-- Botón para abrir el diálogo de archivos -->
            <x-secondary-button type="button" @click="openFileDialog()" class="mt-4 md:mt-0 w-full md:w-auto">
                <i class="fa fa-file-excel"></i>
                Importar Empleados
            </x-secondary-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" x-ref="fileInput"
                style="display: none;" wire:model.live="file" />
        </div>
        <div>
            <x-secondary-button type="button" wire:click="export" class="mt-4 md:mt-0 w-full md:w-auto">
                <i class="fa fa-file-excel"></i>
                Exportar Empleados
            </x-secondary-button>
        </div>
    </div>
    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="">
                    Lista de Empleados No incluidos
                    <x-label>
                        En el sistema ya existia la siguiente lista de empleados, pero esta información no estaba en el Excel que acaba de importar, si desea eliminarlos, puede hacerlo mediante este formulario.
                    </x-label>
                </div>
                <div class="flex-shrink-0">
                    <button wire:click="cerrarForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" />
                        <x-th value="Documento" />
                        <x-th value="Nombre Completo" />
                        <x-th value="Orden" />
                        <x-th value="Asignación Familiar" />
                        <x-th value="SNP/SPP" />
                        <x-th value="Cargo" />
                        <x-th value="Fech. Nac." />
                        <x-th value="Fech. Ingreso." />
                        <x-th value="Género" />
                        <x-th value="Acciones" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($empleadosNoImportadosQuery && $empleadosNoImportadosQuery->count())
                        @foreach ($empleadosNoImportadosQuery as $indice => $empleado)
                            <x-tr style="background-color:{{ $empleado->grupo ? $empleado->grupo->color : '#ffffff' }}">
                                <x-th value="{{ $indice + 1 }}" />
                                <x-td value="{{ $empleado->documento }}" />
                                <x-td value="{{ $empleado->nombreCompleto }}"  class="!text-left" />
                                <x-td>
                                    <div class="flex items-center gap-2">
                                        <x-success-button wire:click="moveUp({{ $empleado->id }})"
                                            class="">
                                            <i class="fa fa-arrow-up"></i>
                                        </x-success-button>
                                        <x-input class="!w-12 !p-2 !mt-0 text-center" value="{{ $empleado->orden }}" wire:keyup.debounce.500ms="moveAt({{ $empleado->id }}, $event.target.value)" />
                                        <x-button wire:click="moveDown({{ $empleado->id }})"
                                            class="">
                                            <i class="fa fa-arrow-down"></i>
                                        </x-button>
                                    </div>
                                </x-td>
                                <x-td>
                                    <x-secondary-button wire:click="asignacionFamiliar('{{ $empleado->code }}')">
                                        {{ $empleado->tieneAsignacionFamiliar['mensaje'] }}
                                    </x-secondary-button>
                                </x-td>
                                <x-td value="{{ $empleado->descuento_sp_id }}" />
                                <x-td value="{{ isset($empleado->cargo) ? $empleado->cargo->nombre : '-' }}"
                                    />
                                <x-td value="{{ $empleado->fecha_nacimiento }}" />
                                <x-td value="{{ $empleado->fecha_ingreso }}" />
                                <x-td value="{{ $empleado->genero }}" />
                                <x-td>
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($empleado->status != 'activo')
                                            <x-warning-button wire:click="enable('{{ $empleado->code }}')">
                                                <i class="fa fa-ban"></i>
                                            </x-warning-button>
                                        @else
                                            <x-success-button wire:click="disable('{{ $empleado->code }}')">
                                                <i class="fa fa-check"></i>
                                            </x-success-button>
                                        @endif
                                        <x-button wire:click="editar('{{ $empleado->code }}')">
                                            <i class="fa fa-pencil"></i>
                                        </x-button>
                                        <x-danger-button wire:click="confirmarEliminacion('{{ $empleado->code }}')">
                                            <i class="fa fa-remove"></i>
                                        </x-danger-button>
                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Empleados registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-slot>
        <x-slot name="footer">
            <x-button type="button" wire:click="cerrarForm">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
