@props([
    'label' => 'Color',
    'colors' => [],
])

@php
    /* Lista interna por defecto */
    $defaultColors = [
        [ 'hex' => '#FF6467', 'nombre' => 'Rojo Coral' ],
        [ 'hex' => '#FF8904', 'nombre' => 'Naranja Intenso' ],
        [ 'hex' => '#FFB900', 'nombre' => 'Amarillo Mostaza' ],
        [ 'hex' => '#9AE600', 'nombre' => 'Verde Lima' ],
        [ 'hex' => '#05DF72', 'nombre' => 'Verde Esmeralda' ],
        [ 'hex' => '#51A2FF', 'nombre' => 'Azul Claro' ],
        [ 'hex' => '#7C86FF', 'nombre' => 'Azul Lavanda' ],
        [ 'hex' => '#A684FF', 'nombre' => 'Violeta Suave' ],
        [ 'hex' => '#ED6BFF', 'nombre' => 'Fucsia' ],
        [ 'hex' => '#FF637E', 'nombre' => 'Rosa Brillante' ],
        [ 'hex' => '#90A1B9', 'nombre' => 'Gris Azulado' ],
        [ 'hex' => '#A6A09B', 'nombre' => 'Gris Cálido' ],
    ];

    /** Merge: combinamos internos + externos */
    $mergedColors = array_merge($defaultColors, $colors);
@endphp


<div 
    x-data="{
        open: false,
        colors: @js($mergedColors),

        // El modelo Livewire contiene SOLO el HEX
        model: @entangle($attributes->wire('model')),

        // Estado interno del picker (objeto completo)
        selected: { hex: '', nombre: '' },

        init() {
            // Sincronizar el picker interno con el valor inicial del modelo externo
            if (this.model) {
                let match = this.colors.find(c => c.hex === this.model);
                if (match) this.selected = match;
            }
        },

        toggle() { this.open = !this.open },
        close() { this.open = false },

        select(color) {
            this.selected = color;

            // Se envía SOLO el HEX a Livewire
            this.model = color.hex;

            this.close();
        }
    }"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>

    <x-label :value="$label" />

    <div class="flex justify-center mt-1">
        <div 
            x-on:keydown.escape.prevent.stop="close()" 
            x-on:focusin.window="!$refs.panel.contains($event.target) && close()"
            x-id="['cp-dropdown']"
            class="relative w-full"
        >

            <!-- Botón -->
            <button 
                x-ref="button" 
                x-on:click="toggle()"
                :aria-expanded="open"
                :aria-controls="$id('cp-dropdown')"
                type="button"
                class="relative flex items-center justify-between gap-2 py-2 px-4 w-full
                       rounded-lg shadow-sm bg-muted border border-border
                       dark:border-meta-4"
            >
                <div class="flex items-center gap-2 text-foreground">
                    <div class="w-5 h-5 rounded border border-border"
                         :style="`background-color: ${selected.hex || '#ffffff'}`"></div>
                    <span x-text="selected.nombre || 'Seleccionar color'"></span>
                </div>

                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 16 16" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"/>
                </svg>
            </button>

            <!-- Panel -->
            <div 
                x-ref="panel"
                x-show="open"
                x-transition.origin.top.left
                x-on:click.outside="close()"
                :id="$id('cp-dropdown')"
                x-cloak
                class="absolute left-0 min-w-48 rounded-lg shadow-sm mt-2 z-10 p-1.5 
                       border border-border bg-card"
            >
                <template x-for="color in colors" :key="color.hex">
                    <button 
                        type="button"
                        class="w-full flex items-center gap-2 px-2 py-2 rounded hover:bg-muted text-card-foreground"
                        @click="select(color)"
                    >
                        <div :style="'background-color: ' + color.hex"
                             class="w-5 h-5 rounded border"></div>
                        <span x-text="color.nombre"></span>
                    </button>
                </template>
            </div>

        </div>
    </div>
</div>
