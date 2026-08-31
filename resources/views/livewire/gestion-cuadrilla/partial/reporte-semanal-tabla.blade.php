<x-flex class="mb-3 justify-between">
    <x-flex>
        <div wire:ignore>
            <x-input
                type="text"
                placeholder="Buscar por nombre..."
                x-model.debounce.300ms="busquedaNombre"
                autocomplete="off"
                spellcheck="false"
                name="busqueda_cuadrillero_no_autocomplete"
            />
        </div>

        <x-select wire:ignore.self x-model="filtroGrupo" class="w-auto" placeholder="Filtrar por grupo">
            <option value="">Todos los grupos</option>
            @foreach ($listaGrupos as $grupo)
                <option wire:key="grupo-filtro-{{ $grupo['codigo'] }}" value="{{ $grupo['codigo'] }}">
                    {{ $grupo['nombre'] ?? $grupo['codigo'] }}
                </option>
            @endforeach
        </x-select>
    </x-flex>
    <div>
        @include('livewire.gestion-cuadrilla.partial.reporte-semanal-opciones')
    </div>
</x-flex>

<div wire:ignore class="mt-5">
    <div x-ref="tableContainerSemana"></div>
</div>

{{-- Panel flotante de búsqueda (solo aparece con scroll hacia abajo) --}}
<div
    x-show="mostrarBusquedaFlotante"
    x-transition.opacity
    @click.outside="mostrarBusquedaFlotante = false"
    @keydown.escape.window="mostrarBusquedaFlotante = false"
    class="fixed bottom-24 left-6 z-[1001] bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl p-4 flex flex-col gap-3 w-[min(90vw,26rem)]"
    style="display: none;"
>
    <div class="flex justify-between items-center">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Buscar cuadrillero</span>
        <button type="button" @click="mostrarBusquedaFlotante = false"
            class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-lg leading-none">
            <i class="fa fa-times"></i>
        </button>
    </div>

    <div wire:ignore>
        <input
            type="text"
            x-ref="inputBusquedaFlotante"
            x-model.debounce.300ms="busquedaNombre"
            placeholder="Buscar por nombre..."
            autocomplete="off"
            spellcheck="false"
            name="busqueda_flotante_no_autocomplete"
            class="w-full text-lg px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                   bg-gray-50 dark:bg-gray-800 dark:text-white
                   focus:outline-none focus:ring-2 focus:ring-green-500"
        />
    </div>

    <select
        wire:ignore.self
        x-model="filtroGrupo"
        class="w-full text-lg px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
               bg-gray-50 dark:bg-gray-800 dark:text-white
               focus:outline-none focus:ring-2 focus:ring-green-500"
    >
        <option value="">Todos los grupos</option>
        @foreach ($listaGrupos as $grupo)
            <option wire:key="grupo-filtro-flotante-{{ $grupo['codigo'] }}" value="{{ $grupo['codigo'] }}">
                {{ $grupo['nombre'] ?? $grupo['codigo'] }}
            </option>
        @endforeach
    </select>
</div>

{{-- Botón lupa flotante --}}
<div
    x-show="scrolleado && !mostrarBusquedaFlotante"
    x-transition.opacity
    class="fixed bottom-6 left-6 z-[1000]"
    style="display: none;"
>
    <button
        type="button"
        @click="abrirBusquedaFlotante"
        title="Buscar"
        class="w-14 h-14 rounded-full bg-green-600 hover:bg-green-700 text-white
               shadow-xl flex items-center justify-center text-xl transition"
    >
        <i class="fa fa-search"></i>
    </button>
</div>

<x-inferior-derecha>
    <x-button @click="registrarHoras">
        <i class="fa fa-save"></i> Guardar cambios
    </x-button>
</x-inferior-derecha>