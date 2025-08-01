<div class="space-y-4">
    <x-card2>
        <x-flex class="justify-between space-y-4 md:space-y-0">
            <div>
                <x-h3>
                    Gestión de Mano de Obra
                </x-h3>
                <x-label>
                    Administra los tipos de mano de obra y sus estadísticas
                </x-label>
            </div>
            <div>
                <x-button wire:click="abrirFormManoObra" wire:loading.attr="disabled">
                    <i class="fa fa-plus"></i> Agregar mano de obra
                </x-button>
            </div>
        </x-flex>
    </x-card2>
    <x-card2>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>#</x-th>
                    <x-th>Código</x-th>
                    <x-th>Descripción</x-th>
                    <x-th class="w-80 text-center">Acciones</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($manoObras as $indice => $manoObra)
                    <x-tr>
                        <x-td>{{ $indice + 1 }}</x-td>
                        <x-td>{{ $manoObra->codigo }}</x-td>
                        <x-td>{{ $manoObra->descripcion }}</x-td>
                        <x-td class="text-center">
                            <x-flex class="justify-center">
                                <x-button wire:click="abrirFormManoObra('{{ $manoObra->codigo }}')">
                                    <i class="fa fa-save"></i><span class="hidden md:inline-block"> Editar</span>
                                </x-button>
                                <x-danger-button wire:click="eliminarManoObra('{{ $manoObra->codigo }}')">
                                    <i class="fa fa-trash"></i><span class="hidden md:inline-block"> Eliminar</span>
                                </x-danger-button>
                            </x-flex>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-slot>
        </x-table>
    </x-card2>

    <x-dialog-modal wire:model.live="mostrarFormularioManoObra">
        <x-slot name="title">
            Registro de Mano de Obra
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <x-input-string label="Código" wire:model="codigo" error="codigo" />
                <x-input-string label="Descripción" wire:model="descripcion" error="descripcion" />
               
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormularioManoObra', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
            <x-button wire:click="guardarManoObra" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>