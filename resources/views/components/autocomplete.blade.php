@props([
    'sugerencias' => [],
    'placeholder' => 'Escribe para buscar...',
    'label' => null,
])

<div 
    x-data="autocomplete({
        suggestions: @js($sugerencias),
        model: @entangle($attributes->wire('model')),
    })" 
    class="relative w-full"
>
    @if ($label)
        <x-label>{{ $label }}</x-label>
    @endif

    <x-input type="text"
        x-model="model"
        @focus="open = true"
        @input="open = true" 
        @keydown.arrow-down.prevent="highlightNext()"
        @keydown.arrow-up.prevent="highlightPrev()"
        @keydown.enter.prevent="selectHighlighted()"
        @keydown.tab="closeDropdown()" 
        @blur="handleBlur($event)"
        {{ $attributes->whereStartsWith('wire:model') }}
        class="w-full border rounded px-3 py-2"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
    />

    <div 
        x-show="open && filtered.length"
        @mousedown.prevent
        class="absolute left-0 mt-1 w-full bg-white border border-gray-300 shadow-lg z-[999] rounded max-h-56 overflow-y-auto dark:bg-gray-800"
    >
        <template x-for="(item, index) in filtered" :key="index">
            <div 
                @click="select(item)"
                @mouseenter="highlight = index"
                :class="highlight === index ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'"
                class="px-3 py-2 cursor-pointer"
            >
                <span x-text="item"></span>
            </div>
        </template>
    </div>
</div>

@script
<script>
Alpine.data('autocomplete', ({ suggestions, model }) => ({
    model,
    open: false,
    highlight: -1,
    suggestions,

    get filtered() {
        if (!this.model) return this.suggestions;
        return this.suggestions.filter(item =>
            item.toLowerCase().includes(this.model.toLowerCase())
        );
    },

    highlightNext() {
        if (!this.filtered.length) return;
        this.highlight = (this.highlight + 1) % this.filtered.length;
        this.open = true;
    },

    highlightPrev() {
        if (!this.filtered.length) return;
        this.highlight = (this.highlight - 1 + this.filtered.length) % this.filtered.length;
        this.open = true;
    },

    selectHighlighted() {
        if (this.highlight >= 0) {
            this.select(this.filtered[this.highlight]);
        }
    },

    select(item) {
        this.model = item;
        this.open = false;
        this.highlight = -1;
    },

    closeDropdown() {
        this.open = false;
        this.highlight = -1;
    },

    handleBlur(e) {
        // Si el blur es hacia el menú interno, no cerrar
        if (e.relatedTarget && e.relatedTarget.closest('[x-data]')) {
            return;
        }
        this.closeDropdown();
    }
}));
</script>
@endscript
