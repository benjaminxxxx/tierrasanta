<div class="flex flex-col h-full">
    {{-- Header --}}
    <div class="bg-slate-800 bg-opacity-50 rounded-t-lg border border-b-0 border-slate-700 p-4">
        <h2 class="text-lg font-semibold text-slate-200">
            Sin Asignar
        </h2>
        <p class="text-xs text-slate-500 mt-1">
            <span x-text="sinAsignar.length"></span> salidas pendientes
        </p>
    </div>

    {{-- Body --}}
    <div class="flex-1 bg-slate-900 bg-opacity-30 border border-slate-700 rounded-b-lg overflow-y-auto p-4 space-y-3">
        {{-- Estado vacío --}}
        <template x-if="sinAsignar.length === 0">
            <div class="flex items-center justify-center h-32 text-slate-500">
                <p class="text-sm">
                    Todas las salidas asignadas ✓
                </p>
            </div>
        </template>

        {{-- Lista de salidas --}}
        <template x-for="salida in sinAsignar" :key="salida.id">
            <div @click="selectOne(salida, $event.shiftKey)"
                :class="selected.has(salida.id) ?
                    'border-blue-400 bg-blue-900 bg-opacity-40' :
                    'border-slate-600 border-opacity-50 bg-slate-800 bg-opacity-20 hover:border-slate-500 hover:bg-opacity-30'"
                class="p-3 rounded-lg border-2 transition-all cursor-pointer group">
                <div class="flex items-start gap-3">
                    <input type="checkbox" class="mt-1 cursor-pointer w-4 h-4" :checked="selected.has(salida.id)"
                        @click.stop @change="selectOne(salida, false)" />

                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-300">
                            Salida • Campo <span x-text="salida.campo"></span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1" x-text="salida.fecha"></p>
                    </div>

                    <div class="text-right">
                        <div class="text-lg font-bold text-slate-200" x-text="salida.cantidad.toFixed(1)"></div>
                        <div class="text-xs text-slate-500">
                            kg
                        </div>
                    </div>
                </div>
        </template>
    </div>

    {{-- Acciones múltiples --}}
    <template x-if="selected.size > 0">
        <div class="mt-4 flex gap-2 sticky bottom-0 justify-between">
            <x-button @click="asignarSeleccionados('blanco')">
                <i class="fa fa-arrow-right"></i> A Blanco
            </x-button>

            <x-button @click="asignarSeleccionados('negro')" variant="secondary">
                <i class="fa fa-arrow-right"></i> A Negro
            </x-button>
        </div>
    </template>
</div>
