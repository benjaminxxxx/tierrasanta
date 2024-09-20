<div>
    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Asignación de Horas de Riego
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="lg:flex items-center gap-3">
                <div class="my-4">
                    <x-label for="fecha">Fecha</x-label>
                    {{$fecha}}
                </div>
                @if ($regadores)
                    <div class="my-4">
                        <x-label for="regador">Encargado</x-label>
                       {{$regadorNombre}}
                    </div>
                @endif
            </div>
            <div>
                <x-label for="activar_copiar_excel" class="mt-4">
                    <x-checkbox id="activar_copiar_excel" wire:model.live="activarCopiarExcel" class="mr-2" />
                    {{$activarCopiarExcel == true ? 'Activar' : 'Desactivar'}} Copiar desde Excel
                </x-label>
                @if ($activarCopiarExcel == true)
                    <x-textarea rows="8" class="mt-6 mb-2"
                        placeholder="Copie los datos de la tabla de de Excel y péguelos aquí"
                        wire:model="informacionExcel"></x-textarea>
                @endif

            </div>
            @if ($regador && $activarCopiarExcel == false)
                <x-table class="mt-5">
                    <x-slot name="thead">
                        <tr>
                            <x-th value="Campo" class="text-center" />
                            <x-th value="Inicio" class="text-center" />
                            <x-th value="Fin" class="text-center" />
                            <x-th value="Total de Horas" class="text-center" />
                            <x-th />
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @if (is_array($campos) && count($campos) > 0)
                            @foreach ($campos as $indice => $campob)
                                <x-tr>
                                    <x-th class="text-center">
                                        <x-input type="text" class="text-center" value="{{ $campob['nombre'] }}" />
                                    </x-th>
                                    <x-td class="text-center">
                                        <x-input type="time" class="!w-36 text-center"
                                            wire:change="clacularTotal('{{ $campob['nombre'] }}')"
                                            wire:model="campos.{{ $indice }}.inicio" />
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-input type="time" class="!w-36 text-center"
                                            wire:change="clacularTotal('{{ $campob['nombre'] }}')"
                                            wire:model="campos.{{ $indice }}.fin" />
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-input type="time" class="!w-36 text-center" readonly
                                            wire:model="campos.{{ $indice }}.total" />
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-danger-button title="Eliminar Registro"
                                            wire:click="eliminarIndice({{ $indice }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-td>
                                </x-tr>
                            @endforeach

                        @endif
                    </x-slot>
                </x-table>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cancelar</x-secondary-button>
            <x-button type="button" wire:click="store" class="mr-2">Guardar Horas de Riego</x-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
