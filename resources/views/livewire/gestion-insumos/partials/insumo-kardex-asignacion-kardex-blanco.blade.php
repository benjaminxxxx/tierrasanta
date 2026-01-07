<div
    class="rounded-xl border-2 border-blue-600 bg-blue-950 bg-opacity-40 shadow-lg shadow-blue-500/10
           overflow-hidden transition-all duration-300 flex flex-col h-full">
    @if ($kardexBlanco)
        {{-- Header --}}
        <div class="px-6 py-4 border-b-2 border-blue-600 bg-blue-900 bg-opacity-40">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <x-flex class="justify-between pb-3">
                        <x-h3>
                            📋 Kardex Blanco
                        </x-h3>
                        {{-- Acción contextual --}}
                        <template x-if="hasSelectedIn('blanco')">
                            <button @click="quitarSeleccionados('blanco')"
                                class="h-fit px-4 py-2 text-xs rounded font-medium
                       bg-red-700 hover:bg-red-600 text-white
                       border border-red-600 hover:border-red-500">
                                <i class="fa fa-arrow-left"></i> Quitar seleccionados
                            </button>
                        </template>
                    </x-flex>


                    <div class="grid grid-cols-4 gap-2 text-xs">
                        <x-kardex-stat label="Stock Inicial" color="blue">
                            <span x-text="stockInicialBlanco.toFixed(1) + ' kg'"></span>
                        </x-kardex-stat>

                        <x-kardex-stat label="Compras" color="blue">
                            <span x-text="totalComprasBlanco.toFixed(1) + ' kg'"></span>
                        </x-kardex-stat>

                        <x-kardex-stat label="Salidas" color="blue">
                            <span x-text="totalSalidasBlanco.toFixed(1) + ' kg'"></span>
                        </x-kardex-stat>

                        <x-kardex-balance>
                            <span x-text="balanceBlanco.toFixed(1) + ' kg'"
                                :class="balanceBlanco < 0 ? 'text-red-400' : 'text-emerald-400'"></span>
                        </x-kardex-balance>
                    </div>


                </div>


            </div>
        </div>

        <div class="flex-1 p-6 space-y-2 overflow-y-auto">
            <template x-for="item in kardexBlancoItems" :key="item.id + '-' + item.tipo">
                <div>
                    <template x-if="item.tipo === 'entrada'">
                        <div class="p-3 rounded-lg border-l-4 group transition-all
               bg-blue-900 bg-opacity-30 border-l-blue-500
               border border-blue-700 border-opacity-40
               hover:border-blue-600 hover:bg-opacity-40"
                            @mouseenter="item._hover = true" @mouseleave="item._hover = false">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-slate-200">
                                        Compra
                                    </h4>
                                    <p class="text-xs text-slate-400 mt-1" x-text="item.fecha"></p>
                                </div>

                                <div class="text-right text-blue-400">
                                    <div class="text-lg font-bold" x-text="item.cantidad.toFixed(1)"></div>
                                    <div class="text-xs text-slate-500">kg</div>
                                </div>
                            </div>

                            <div class="text-xs text-slate-500 flex justify-between mb-3">
                                <span>
                                    Unitario:
                                    S/ <span x-text="item.costo_unitario.toFixed(2)"></span>
                                </span>
                                <span class="text-blue-400">
                                    S/
                                    <span x-text="(item.cantidad * item.costo_unitario).toFixed(2)"></span>
                                </span>
                            </div>

                            <div class="flex gap-2 transition-all"
                                :class="item._hover ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                                <x-button @click="autoAjustarCompra(item)" variant="primary">
                                    ⚡ Auto asignar
                                </x-button>
                                <x-button @click="moverCompra(item)" variant="secondary">
                                    <i class="fa fa-exchange"></i>
                                    <span
                                        x-text="item.tipo_kardex === 'blanco' ? 'Pasar a Negro' : 'Pasar a Blanco'"></span>
                                </x-button>


                                <x-button @click="quitarSalidasCompra(item)" variant="danger">
                                    <i class="fa fa-trash"></i> Quitar todos
                                </x-button>
                            </div>
                        </div>
                    </template>


                    <template x-if="item.tipo === 'salida'">
                        <div @click="selectOne(item, $event.shiftKey)" @contextmenu.prevent="selectOne(item.id, true)"
                            :class="{
                                // Seleccionado
                                'bg-blue-800 bg-opacity-60 border-l-blue-300 border-2 border-blue-500 shadow-lg shadow-blue-500/30': selected
                                    .has(item.id) && item.tipo_kardex === 'blanco',
                            
                                // Modificado pero no seleccionado
                                'bg-blue-900 bg-opacity-50 border-l-blue-400 border border-blue-600 shadow-md shadow-blue-500/20':
                                    !selected.has(item.id) && changes.has(item.id) && item.tipo_kardex === 'blanco',
                            
                                // Normal
                                'bg-blue-900 bg-opacity-20 border-l-blue-500 border border-blue-700 border-opacity-30':
                                    !selected.has(item.id) && !changes.has(item.id) && item.tipo_kardex === 'blanco',
                            }"
                            class="p-3 rounded-lg border-l-4 transition-all cursor-pointer group mb-3">
                            {{-- Checkbox + info --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="mt-1 w-4 h-4 cursor-pointer"
                                        :checked="selected.has(item.id)" @click.stop
                                        @change="selectOne(item.id, false)" />

                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="text-sm font-semibold text-slate-200">
                                                Salida • Campo <span x-text="item.campo"></span>
                                            </h4>
                                        </div>

                                        <p class="text-xs text-slate-400" x-text="item.fecha"></p>
                                    </div>
                                </div>

                                <div class="text-right"
                                    :class="item.tipo_kardex === 'blanco' ? 'text-blue-400' : 'text-slate-400'">
                                    <div class="text-lg font-bold" x-text="item.cantidad.toFixed(1)"></div>
                                    <div class="text-xs text-slate-500">kg</div>
                                </div>
                            </div>

                            {{-- Quitar individual --}}
                            <button @click.stop="quitarDeKardex(item.id)"
                                class="w-full text-xs py-2 px-2 rounded font-medium transition-all
                   bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-slate-100
                   border border-slate-600 hover:border-slate-500">
                                <i class="fa fa-arrow-left"></i> Sacar
                            </button>
                        </div>
                    </template>

                </div>
            </template>

            <template x-if="kardexBlancoItems.length === 0">
                <div class="text-center py-10 text-slate-500">
                    No hay movimientos en este Kardex
                </div>
            </template>
        </div>
    @else
        <x-warning>
            No hay Kardex Blanco <x-button href="{{ route('gestion_insumos.kardex') }}">Crear Kardex</x-button>
        </x-warning>
    @endif
</div>
