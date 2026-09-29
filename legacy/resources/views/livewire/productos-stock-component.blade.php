<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarVista" maxWidth="full">
        <x-slot name="title">
            Stock disponible de cada producto
        </x-slot>

        <x-slot name="content">
            <x-flex class="my-3">
                <div>
                    <x-label value="Buscar por nombre de producto" />
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                            <i class="fa fa-search"></i>
                        </div>
                        <x-input type="search" wire:model.live.debounce.800ms="search" id="default-search" class="!pl-10"
                            autocomplete="off" placeholder="Busca tu producto digitando aqui..." required />
                    </div>
                </div>
            </x-flex>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-1">
                @foreach ($productos as $producto)
                    <div class="flex flex-col h-full p-3">
                        <!-- Título -->
                        <x-h3 class="w-full text-center !text-md my-2 min-h-[50px]">
                            {{ $producto->nombre_completo }}
                        </x-h3>

                        <!-- Contenedor central flexible -->
                        <div class="flex-grow flex flex-col items-center justify-center">
                            @if ($producto->compras->count() > 0)
                                <div
                                    class="barril-container w-full h-[100px] bg-gray-200 rounded-lg relative overflow-hidden shadow">
                                    <div class="barril-fill w-full absolute bottom-0 bg-green-500 transition-all duration-500"
                                        style="height: {{ $producto->stock_disponible['stock_disponible_porcentaje'] }}%;">
                                    </div>
                                </div>
                                <p class="text-center mt-4">
                                    <b>Stock Disponible:</b>
                                    {{ $producto->stock_disponible['stock_disponible'] . $producto->unidad_medida }}
                                </p>
                            @else
                                <div class="flex flex-col items-center text-center my-5">
                                    <i class="fa fa-warning text-4xl text-yellow-600"></i>
                                    <p>Sin ninguna compra realizada.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Botón siempre en la base -->
                        <div class="mt-auto text-center">
                            <x-button type="button"
                                @click="$wire.dispatch('VerComprasProducto',{id:{{ $producto->id }}})"
                                class="mt-3 w-full">
                                <i class="fa fa-money-bill"></i> Ver compras
                            </x-button>
                        </div>
                    </div>
                @endforeach
            </div>

        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="$set('mostrarVista', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
