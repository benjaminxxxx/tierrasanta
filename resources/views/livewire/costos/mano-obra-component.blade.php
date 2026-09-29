<div class="space-y-4">
    <x-flex class="w-full justify-between">
        <div>
            <x-title>
                Gestión de Mano de Obra
            </x-title>
            <x-subtitle>
                Administra los tipos de mano de obra y sus estadísticas
            </x-subtitle>
        </div>
        <div>
            @can(\App\Constants\Permisos::CAMPO_MANO_OBRA_GESTIONAR)
                <x-button wire:click="abrirFormManoObra" wire:loading.attr="disabled">
                    <i class="fa fa-plus"></i> Agregar mano de obra
                </x-button>
            @endcan

        </div>
    </x-flex>
    <x-card>
        @can(\App\Constants\Permisos::CAMPO_MANO_OBRA_VER)
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
                                @can(\App\Constants\Permisos::CAMPO_MANO_OBRA_GESTIONAR)
                                    <x-flex class="justify-center">
                                        <x-button wire:click="abrirFormManoObra('{{ $manoObra->codigo }}')" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-button variant="danger" wire:click="eliminarManoObra('{{ $manoObra->codigo }}')"
                                            title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    </x-flex>
                                @endcan
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        @else
            <x-danger>
                No tienes permisos para ver los tipos de mano de obra. Por favor, contacta al administrador.
            </x-danger>
        @endcan
    </x-card>

    <x-dialog-modal wire:model.live="mostrarFormularioManoObra">
        <x-slot name="title">
            Registro de Mano de Obra
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <x-input label="Código" wire:model="codigo" error="codigo" />
                <x-input label="Descripción" wire:model="descripcion" error="descripcion" />

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioManoObra', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="guardarManoObra" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>