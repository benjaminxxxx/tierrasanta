<div>

    <x-loading wire:loading wire:target="seleccionarProducto" />
    <x-loading wire:loading wire:target="verBlanco" />

    <x-flex>
        <x-h3>
            <a href="{{ route('kardex.lista') }}" class="underline text-blue-600">Kardex</a> / {{ $kardex->nombre }}
        </x-h3>
    </x-flex>
    <x-card class="my-4 w-full">
        <x-spacing>
            @if ($producto)
                <div>
                    {{ $producto->nombre_completo }} <x-danger-button wire:click="quitarProducto"><i
                            class="fa fa-remove"></i></x-danger-button>
                </div>
                <div>
                    <livewire:compra-producto-import-export-component productoid="{{ $producto->id }}"
                        kardexId="{{ $this->kardexId }}" wire:key="registroCompraForCompra{{ $producto->id }}" />
                </div>
                <div>
                    <x-button type="button" @click="$wire.dispatch('EditarProducto',{id:{{ $producto->id }}})"
                        class="mt-4 md:mt-0 w-full md:w-auto">
                        <i class="fa fa-edit"></i>
                        Editar Producto
                    </x-button>
                </div>
            @else
                <x-flex class="justify-between mt-2 w-full">
                    <div>
                        <x-label>Busca tu producto por nombre o agente activo</x-label>
                        <div class="relative w-full">
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                                <i class="fa fa-search"></i>
                            </div>
                            <x-input type="search" wire:model.live="search" id="default-search" class="!w-auto !pl-10"
                                autocomplete="off" required />
                            <div wire:loading wire:target="search">
                                Cargando <i class="fa fa-spin fa-rotate"></i>
                            </div>
                            @if (!empty($resultado))
                                <div
                                    class="absolute z-10 bg-white border border-gray-300 mt-1 max-w-[20rem] rounded-lg shadow-lg">
                                    <ul>
                                        @foreach ($resultado as $producto)
                                            <li class="p-2 hover:bg-gray-100 cursor-pointer"
                                                wire:click="seleccionarProducto({{ $producto->id }})">
                                                {{ $producto->nombre_completo }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <x-toggle-switch :checked="$verBlanco" label="Ver Blanco" wire:model.live="verBlanco" />

                    </div>
                </x-flex>

            @endif

        </x-spacing>
    </x-card>
    @if ($productoSeleccionadoId)
        @if ($producto)
            <livewire:kardex-distribucion-component :kardexId="$kardexId" :kardexProductoId="$producto->id"
                wire:key="kardexProductoId{{ $productoSeleccionadoId }}" />
        @endif
    @else
        <livewire:kardex-indice-component :kardexId="$kardexId" :verBlanco="$verBlanco"
            wire:key="kardexGeneral{{ $kardexId }}_{{ $verBlanco }}" />
    @endif
</div>
