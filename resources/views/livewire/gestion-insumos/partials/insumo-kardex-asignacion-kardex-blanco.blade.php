<div class="rounded-xl border border-blue-200 dark:border-blue-700
            bg-white dark:bg-slate-900 
            shadow-lg shadow-blue-200/50 dark:shadow-blue-900/40
            overflow-hidden transition-all duration-300 flex flex-col h-full">

    @if ($kardexBlanco)
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-blue-200 dark:border-blue-700 
                    bg-blue-50 dark:bg-blue-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <x-flex class="justify-between pb-3">
                        <x-h3>
                            📋 Kardex Blanco
                        </x-h3>

                        <template x-if="hasSelectedIn('blanco')">
                            <x-button @click="quitarSeleccionados('blanco')" variant="danger">
                                <i class="fa fa-arrow-left"></i> Quitar seleccionados
                            </x-button>
                        </template>

                        <x-button @click="autoAsignarTodo('blanco')" variant="primary">
                            <i class="fa fa-bolt"></i> Auto asignar → Blanco
                        </x-button>
                    </x-flex>

                    <div class="grid grid-cols-4 gap-2 text-xs">
                        <x-kardex-stat label="Stock Inicial" color="blue">
                            <span x-text="stockInicialBlanco.toFixed(3)"></span>
                        </x-kardex-stat>

                        <x-kardex-stat label="Compras" color="blue">
                            <span x-text="totalComprasBlanco.toFixed(3)"></span>
                        </x-kardex-stat>

                        <x-kardex-stat label="Salidas" color="blue">
                            <span x-text="totalSalidasBlanco.toFixed(3)"></span>
                        </x-kardex-stat>

                        <x-kardex-balance>
                            <span x-text="balanceBlanco.toFixed(3)"
                                  :class="balanceBlanco < 0 ? 'text-red-500' : 'text-emerald-500'"></span>
                        </x-kardex-balance>
                    </div>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 p-6 space-y-3 overflow-y-auto">

            <template x-for="item in kardexBlancoItems" :key="item.id + '-' + item.tipo">
                <div>

                    {{-- ENTRADAS --}}
                    <template x-if="item.tipo === 'entrada'">
                        <div class="p-3 rounded-lg border-l-4 transition-all
                                    bg-blue-50 dark:bg-blue-900
                                    border border-blue-200 dark:border-blue-700
                                    border-l-blue-500 hover:border-blue-400"
                             @mouseenter="item._hover = true"
                             @mouseleave="item._hover = false">

                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        Compra
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="item.fecha"></p>
                                </div>

                                <div class="text-right text-blue-600 dark:text-blue-400">
                                    <div class="text-lg font-bold" x-text="item.cantidad.toFixed(3)"></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        <span x-text="item.unidad_medida"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs text-slate-500 dark:text-slate-400 flex justify-between mb-3">
                                <span>
                                    Unitario: S/
                                    <span x-text="item.costo_unitario.toFixed(3)"></span>
                                </span>
                                <span class="text-blue-600 dark:text-blue-400">
                                    S/
                                    <span x-text="(item.cantidad * item.costo_unitario).toFixed(3)"></span>
                                </span>
                            </div>

                            <div class="flex gap-2"
                                 :class="item._hover ? '' : 'hidden'">

                                <x-button @click="moverCompra(item)" variant="secondary">
                                    <i class="fa fa-exchange"></i>
                                    <span x-text="item.tipo_kardex === 'blanco' ? 'Pasar a Negro' : 'Pasar a Blanco'"></span>
                                </x-button>

                                <x-button @click="quitarSalidasCompra(item)" variant="danger">
                                    <i class="fa fa-trash"></i> Quitar todos
                                </x-button>
                            </div>
                        </div>
                    </template>

                    {{-- SALIDAS --}}
                    <template x-if="item.tipo === 'salida'">
                        <div
                            @click="selectOne(item, $event.shiftKey)"
                            @contextmenu.prevent="selectOne(item.id, true)"
                            :class="{
                                'bg-blue-100 dark:bg-blue-800 border-blue-500 border-2': selected.has(item.id) && item.tipo_kardex === 'blanco',
                                'bg-blue-50 dark:bg-blue-900 border border-blue-400': !selected.has(item.id) && changes.has(item.id),
                                'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700': !selected.has(item.id) && !changes.has(item.id)
                            }"
                            class="p-3 rounded-lg border-l-4 border-l-blue-500 transition-all cursor-pointer group mb-3">

                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox"
                                           class="mt-1 w-4 h-4 cursor-pointer"
                                           :checked="selected.has(item.id)"
                                           @click.stop
                                           @change="selectOne(item.id, false)" />

                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            Salida • Campo <span x-text="item.campo"></span>
                                        </h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="item.fecha"></p>
                                    </div>
                                </div>

                                <div class="text-right text-blue-600 dark:text-blue-400">
                                    <div class="text-lg font-bold" x-text="item.cantidad.toFixed(3)"></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        <span x-text="item.unidad_medida"></span>
                                    </div>
                                </div>
                            </div>

                            <button @click.stop="quitarDeKardex(item.id)"
                                    class="w-full text-xs py-2 px-2 mt-2 rounded font-medium transition-all
                                           bg-slate-100 dark:bg-slate-700
                                           hover:bg-slate-200 dark:hover:bg-slate-600
                                           text-slate-700 dark:text-slate-200
                                           border border-slate-200 dark:border-slate-600">
                                <i class="fa fa-arrow-left"></i> Sacar
                            </button>
                        </div>
                    </template>

                </div>
            </template>

            <template x-if="kardexBlancoItems.length === 0">
                <div class="text-center py-10 text-slate-500 dark:text-slate-400">
                    No hay movimientos en este Kardex
                </div>
            </template>
        </div>

    @else
        <x-warning>
            No hay Kardex Blanco 
            <x-button href="{{ route('gestion_insumos.kardex') }}">
                Crear Kardex
            </x-button>
        </x-warning>
    @endif
</div>