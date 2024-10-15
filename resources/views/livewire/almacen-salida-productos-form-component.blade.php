<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Salida de Almacén
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            @if ($step == 1)
                <div class="col-span-2 md:col-span-1 mt-3 relative">
                    <x-label for="fecha_salida">Fecha de Salida</x-label>
                    <x-input type="date" wire:model.live="fecha_salida" autofocus
                        placeholder="Escriba la fecha de salida..." autocomplete="nope" class="uppercase"
                        id="fecha_salida" />
                </div>
                @if($fecha_salida)
                <div class="col-span-2 md:col-span-1 mt-3 relative">
                    <x-label for="nombre_comercial">Nombre del Producto</x-label>
                    <x-input type="text" wire:model.live="nombre_comercial" autofocus
                        placeholder="Escriba el nombre del producto..." autocomplete="nope" class="uppercase"
                        id="nombre_comercial" />

                    @if (!empty($productos))
                        <div class="absolute z-10 bg-white border border-gray-300 mt-1 w-full rounded-lg shadow-lg">
                            <ul>
                                @foreach ($productos as $producto)
                                    <li class="p-2 hover:bg-gray-100 cursor-pointer"
                                        wire:click="seleccionarProducto({{ $producto->id }})">
                                        {{ $producto->nombre_comercial }} - {{ $producto->ingrediente_activo }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                @endif
            @endif
            @if ($step == 2)
                <div class="col-span-2 md:col-span-1 mt-3 relative">
                    <x-label for="nombre_comercial">Seleccionar Campos</x-label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if ($campos && $campos->count() > 0)
                            @foreach ($campos as $campo)
                                <button wire:click="toggleCampo('{{ $campo->nombre }}')"
                                    class="inline-block px-4 py-2 border rounded-md transition
                                        @if (in_array($campo->nombre, $camposAgregados)) bg-green-500 text-white border-green-500
                                        @else 
                                            bg-white text-gray-700 border-gray-300 @endif">
                                    {{ $campo->nombre }}
                                    @if (in_array($campo->nombre, $camposAgregados))
                                        <i class="fa fa-check ml-2"></i>
                                    @endif
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            @if ($step == 2)
                <x-secondary-button type="button" wire:click="retroceder" wire:loading.attr="disabled"
                    class="mr-2">Atrás</x-secondary-button>
            @endif
            <x-secondary-button type="button" wire:click="closeForm" wire:loading.attr="disabled"
                class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" wire:loading.attr="disabled" class="ml-3">Siguiente</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
