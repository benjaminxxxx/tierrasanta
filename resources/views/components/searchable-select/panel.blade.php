{{-- Parte visual compartida de x-searchable-select y x-select searchable (lógica: resources/js/components/searchable-select.js) --}}
@props(['modo' => 'fija', 'minimo' => 2, 'disabled' => false, 'placeholder' => 'Buscar...'])

<div class="relative">
    <x-input type="text" x-ref="entrada" x-model="texto" autocomplete="off" :disabled="$disabled"
        @input="alEscribir()" @focus="abrir()" @click="abrir()"
        @keydown.arrow-down.prevent="bajar()" @keydown.arrow-up.prevent="subir()"
        @keydown.enter.prevent="elegirResaltado()" @keydown.tab="cerrar()"
        placeholder="{{ $placeholder }}" class="w-full pr-14" />

    <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
        <i x-show="cargando" x-cloak class="fa fa-spinner fa-spin text-xs text-muted-foreground"></i>
        <button type="button" x-show="(seleccion || texto.length > 0) && !@js((bool) $disabled)" x-cloak
            @click="limpiar()" title="Limpiar selección" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        @if ($modo !== 'buscar')
            <button type="button" tabindex="-1" @click="alternar()" class="text-gray-400">
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': abierto }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        @endif
    </div>
</div>

<ul x-ref="lista" x-show="abierto && lista.length > 0" x-cloak
    class="absolute z-[999] w-full mt-1 bg-muted border border-border rounded-md shadow-lg max-h-60 overflow-auto">
    <template x-for="(opcion, indice) in lista" :key="opcion.id">
        <li @mousedown.prevent="elegir(opcion)" @mouseenter="resaltado = indice"
            class="px-4 py-2 cursor-pointer transition-colors duration-150"
            :class="resaltado === indice ? 'bg-card text-card-foreground' : 'text-muted-foreground'"
            x-text="opcion.name"></li>
    </template>
</ul>

<div x-show="abierto && !cargando && lista.length === 0 && (escribiendo && texto.trim().length >= {{ $modo === 'buscar' ? (int) $minimo : 1 }})" x-cloak
    class="absolute z-[999] w-full mt-1 bg-muted border border-border rounded-md shadow-lg px-4 py-2 text-muted-foreground">
    No se encontraron resultados
</div>
@if ($modo === 'buscar')
    <div x-show="abierto && escribiendo && texto.trim().length > 0 && texto.trim().length < {{ (int) $minimo }}" x-cloak
        class="absolute z-[999] w-full mt-1 bg-muted border border-border rounded-md shadow-lg px-4 py-2 text-xs text-muted-foreground">
        Escribe al menos {{ (int) $minimo }} letras…
    </div>
@endif
