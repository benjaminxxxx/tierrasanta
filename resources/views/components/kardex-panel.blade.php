@props([
    'tipo', // 'blanco' | 'negro'
    'titulo', // Kardex Blanco / Negro
    'icono', // 📋
    'kardexExiste', // bool
])

@php
    $classContainer =
        $tipo === 'blanco'
            ? 'border-blue-600 bg-blue-950 bg-opacity-40 shadow-lg shadow-blue-500/10'
            : 'border-slate-600 bg-slate-900 bg-opacity-40 shadow-lg shadow-slate-600/10';
@endphp

<div class="rounded-xl border-2 overflow-hidden transition-all duration-300 flex flex-col h-full {{ $classContainer }}">
    @if ($kardexExiste)
        {{-- HEADER --}}
        <div class="px-6 py-4 border-b-2 bg-opacity-40"
            :class="tipo === 'blanco'
                ?
                'border-blue-600 bg-blue-900' :
                'border-slate-600 bg-slate-800'">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold mb-3"
                        :class="tipo === 'blanco'
                            ?
                            'text-blue-300' :
                            'text-slate-300'">
                        <span x-text="tipo === 'blanco' ? '📋 Kardex Blanco' : '📑 Kardex Negro'"></span>
                    </h2>

                    <div class="grid grid-cols-4 gap-2 text-xs">

                        {{-- Stock Inicial --}}
                        <div class="bg-slate-800 bg-opacity-50 p-3 rounded-lg border border-slate-700">
                            <p class="text-slate-400 mb-1">Stock Inicial</p>
                            <p class="text-lg font-bold"
                                :class="tipo === 'blanco' ? 'text-blue-400' : 'text-slate-400'"
                                x-text="stockInicial.toFixed(1) + ' kg'"></p>
                        </div>

                        {{-- Compras --}}
                        <div class="bg-slate-800 bg-opacity-50 p-3 rounded-lg border border-slate-700">
                            <p class="text-slate-400 mb-1">Compras</p>
                            <p class="text-lg font-bold"
                                :class="tipo === 'blanco' ? 'text-blue-400' : 'text-slate-400'"
                                x-text="totalCompras.toFixed(1) + ' kg'"></p>
                        </div>

                        {{-- Salidas --}}
                        <div class="bg-slate-800 bg-opacity-50 p-3 rounded-lg border border-slate-700">
                            <p class="text-slate-400 mb-1">Salidas</p>
                            <p class="text-lg font-bold"
                                :class="tipo === 'blanco' ? 'text-blue-400' : 'text-slate-400'"
                                x-text="totalSalidas.toFixed(1) + ' kg'"></p>
                        </div>

                        {{-- Balance --}}
                        <div class="p-3 rounded-lg border"
                            :class="balance >= 0 ?
                                'bg-green-900 bg-opacity-30 border-green-700' :
                                'bg-red-900 bg-opacity-30 border-red-700'">
                            <p class="text-slate-400 mb-1">Balance</p>
                            <p class="text-lg font-bold" :class="balance >= 0 ? 'text-green-400' : 'text-red-400'"
                                x-text="balance.toFixed(1) + ' kg'"></p>
                        </div>
                    </div>
                </div>

                {{-- Acción contextual --}}
                <template x-if="hasSelectedIn(tipo)">
                    <button @click="quitarSeleccionados(tipo)"
                        class="h-fit px-4 py-2 text-xs rounded font-medium
                       bg-red-700 hover:bg-red-600 text-white
                       border border-red-600 hover:border-red-500">
                        <i class="fa fa-arrow-left"></i>
                        Quitar seleccionados
                    </button>
                </template>
            </div>
        </div>


        {{-- STATS --}}
        {{-- ... --}}

        {{-- LISTA --}}
        {{-- ... --}}
    @else
        <x-warning>
            No hay {{ $titulo }}
            <x-button href="{{ route('gestion_insumos.kardex') }}">
                Crear Kardex
            </x-button>
        </x-warning>
    @endif
</div>
