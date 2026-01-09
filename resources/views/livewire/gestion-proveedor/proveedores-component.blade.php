<div>
    <x-card>
        <x-flex>
            <x-title>
                Gestión de Proveedores
            </x-title>
            <x-button type="button" @click="$wire.dispatch('crearProveedor')" class="w-full md:w-auto ">
                <i class="fa fa-plus"></i> Nuevo Proveedor
            </x-button>
        </x-flex>
        <form class="flex mt-4">
            <div class="relative w-full">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                    <i class="fa fa-search"></i>
                </div>
                <x-input type="search" wire:model.live="search" id="default-search" class="!w-auto !pl-10"
                    autocomplete="off" placeholder="Busca por Nombre" required />
            </div>
        </form>
        <x-table class="mt-5">
            <x-slot name="thead">
                <tr>
                    <x-th value="N°" class="text-center" />
                    <x-th value="Nombre" />
                    <x-th value="Ruc" class="text-center" />
                    <x-th value="Número de contacto" class="text-center" />
                    <x-th value="Acciones" class="text-center" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($proveedores && $proveedores->count() > 0)
                    @foreach ($proveedores as $indice => $proveedor)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            <x-td value="{{ $proveedor->nombre }}" />
                            <x-td value="{{ $proveedor->ruc }}" class="text-center" />
                            <x-td value="{{ $proveedor->contacto }}" class="text-center" />

                            <x-td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button variant="secondary" 
                                        @click="$wire.dispatch('editarProveedor',{id:{{ $proveedor->id }}})">
                                        <i class="fa fa-edit"></i>
                                    </x-button>
                                    <x-button variant="danger" wire:click="confirmarEliminacion({{ $proveedor->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </div>

                            </x-td>
                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="100%" class="text-center">No Hay Proveedores Registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
        <div class="mt-5">
            {{ $proveedores->links() }}
        </div>
    </x-card>
</div>
