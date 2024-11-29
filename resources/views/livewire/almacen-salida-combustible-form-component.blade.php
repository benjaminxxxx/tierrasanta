<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Salida de Combustible
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
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
                @if ($fecha_salida)
                    <div class="col-span-2 md:col-span-1 mt-3 relative">
                        <x-label for="nombre_comercial">Nombre del Combustible</x-label>
                        <x-input type="search" wire:model.live="nombre_comercial" autofocus
                            placeholder="Escriba el nombre del producto..." autocomplete="off" class="uppercase"
                            id="nombre_comercial" />

                        @if (!empty($productos))
                            <div class="absolute z-10 bg-white border border-gray-300 mt-1 w-full rounded-lg shadow-lg">
                                <ul>
                                    @foreach ($productos as $producto)
                                        <li class="p-2 hover:bg-gray-100 cursor-pointer"
                                            wire:click="seleccionarProducto({{ $producto->id }})">
                                            {{ $producto->nombre_completo }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
            @if ($step == 2)
                <div>
                    <x-label for="seleccionar_almacen">Seleccionar Almacen</x-label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        @if ($almacenes && $almacenes->count() > 0)
                            @foreach ($almacenes as $almacen)
                                @php
                                
                                    $kardexProducto = $almacen->productos()->where('producto_id',$productoSeleccionado->id)->first();                                 
                                    $stockDisponible = $kardexProducto->StockDiponible($fecha_salida);
                                @endphp
                                @if ($stockDisponible > 0)
                                    <x-card
                                        wire:click="seleccionarKardexProducto({{ $kardexProducto->id }},{{ $stockDisponible }})"
                                        class="{{ $almacen->tipo_kardex == 'blanco' ? 'bg-white hover:bg-gray-200 text-gray-900' : '!bg-gray-800 text-white' }} hover:opacity-90 hover:cursor-pointer">
                                        <x-spacing>
                                            <p>{{ $almacen->nombre }} (Tipo Kardex: {{ $almacen->tipo_kardex }})</p>
                                            <p>Stock disponible:
                                                <b>{{ $stockDisponible }}</b>
                                            </p>
                                        </x-spacing>
                                    </x-card>
                                @else
                                    <x-card
                                        class="{{ $almacen->tipo_kardex == 'blanco' ? 'bg-white hover:bg-gray-200 text-gray-900' : '!bg-gray-800 text-white' }} hover:opacity-90 hover:cursor-pointer">
                                        <x-spacing>
                                            <p>{{ $almacen->nombre }} (Tipo Kardex: {{ $almacen->tipo_kardex }})</p>
                                            <p>Stock disponible:
                                                <b>{{ $stockDisponible }}</b>
                                            </p>
                                        </x-spacing>
                                    </x-card>
                                @endif
                            @endforeach
                        @else
                            <p>No se ha registrado un stock para este producto, por favor dirigirse a Kardex</p>
                        @endif
                    </div>
                </div>
            @endif
            @if ($step == 3)
                <div class="col-span-2 md:col-span-1 mt-3 relative">
                    <x-label for="nombre_comercial">Seleccionar Maquinaria</x-label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if ($maquinarias && $maquinarias->count() > 0)
                            @foreach ($maquinarias as $maquinaria)
                                <button wire:click="toggleMaquinaria('{{ $maquinaria->id }}')"
                                    class="inline-block px-4 py-2 border rounded-md transition
                                        @if (in_array($maquinaria->id, $maquinariasAgregadas)) bg-green-500 text-white border-green-500
                                        @else 
                                            bg-white text-gray-700 border-gray-300 @endif">
                                    {{ $maquinaria->nombre }}
                                    @if (in_array($maquinaria->id, $maquinariasAgregadas))
                                        <i class="fa fa-check ml-2"></i>
                                    @endif
                                </button>
                            @endforeach
                        @endif
                    </div>

                    <div class="my-4">
                        @if (is_array($maquinariasAgregadas) && count($maquinariasAgregadas) > 0)
                            <x-table>
                                <x-slot name="thead">
                                    <x-tr>
                                        <x-th>
                                            Maquinaria
                                        </x-th>
                                        <x-th>
                                            Cantidad
                                        </x-th>
                                    </x-tr>
                                </x-slot>
                                <x-slot name="tbody">
                                    @foreach ($maquinariasAgregadas as $campoAgregadoTable)
                                        <x-tr>
                                            <x-th>
                                                {{ $campoAgregadoTable }}
                                            </x-th>
                                            <x-th>
                                                <x-input type="number" class="text-right" wire:model.live="cantidades.{{$campoAgregadoTable}}" />
                                            </x-th>
                                        </x-tr>
                                    @endforeach
                                    <x-tr class="bg-gray-50">
                                        <x-th>
                                            Stock Sumado
                                        </x-th>
                                        <x-th>
                                            
                                            <x-input type="number" readonly class="!bg-gray-100 text-right" value="{{array_sum($cantidades)}}"/>
                                        </x-th>
                                    </x-tr>
                                    <x-tr class="bg-gray-50">
                                        <x-th>
                                            Stock Disponible
                                        </x-th>
                                        <x-th>
                                            <x-input type="number" readonly class="!bg-gray-100 text-right" wire:model="stockDisponibleSeleccionado"/>
                                        </x-th>
                                    </x-tr>
                                </x-slot>
                            </x-table>
                        @endif

                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            @if ($step > 1)
                <x-secondary-button type="button" wire:click="retroceder" wire:loading.attr="disabled"
                    class="mr-2">Atrás</x-secondary-button>
            @endif
            <x-secondary-button type="button" wire:click="$set('mostrarFormulario',false)" wire:loading.attr="disabled"
                class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" wire:loading.attr="disabled" class="ml-3">Siguiente</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
