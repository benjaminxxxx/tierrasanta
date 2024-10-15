<div>
    
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Proveedores
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('CrearProveedor')" class="w-full md:w-auto ">Nuevo Proveedor</x-button>
    </div>
    <x-card>
        <x-spacing>
            <form class="flex">
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
                                        <x-secondary-button @click="$wire.dispatch('EditarProveedor',{id:{{$proveedor->id}}})">
                                            <i class="fa fa-edit"></i>
                                        </x-secondary-button>
                                        <x-danger-button wire:click="confirmarEliminacion({{ $proveedor->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No Hay Proveedores Registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $proveedores->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
