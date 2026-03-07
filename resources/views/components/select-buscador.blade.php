{{-- resources/views/components/searchable-select.blade.php --}}
@props([
    'placeholder' => 'Selecciona una opción',
    'name' => 'searchable_select',
    'value' => '',
    'searchPlaceholder' => 'Buscar...'
])

<div 
    x-data="searchableSelect({
        placeholder: '{{ $placeholder }}',
        initialValue: '{{ $value }}',
        valueEntangle: @entangle($attributes->wire('model')),
        optionsEntangle: @entangle($attributes->wire('options'))  {{-- ✅ Entangle directo de options --}}
    })"
    class="relative w-full"
    @click.away="closeDropdown()"
    @keydown.escape="closeDropdown()"
>
    <!-- Input Field -->
    <div class="relative">
        <x-input 
            type="text"
            x-model="searchTerm"
            x-ref="searchInput"
            @input="filterOptions()"
            @focus="openDropdown()"
            @keydown.arrow-down.prevent="navigateDown()"
            @keydown.arrow-up.prevent="navigateUp()"
            @keydown.enter.prevent="selectHighlighted()"
            @keydown.tab="closeDropdown()"
            x-bind:placeholder="selectedOption ? selectedOption.name : '{{ $searchPlaceholder }}'"
            autocomplete="off"
        />
        
        <!-- Clear Button -->
        <div 
            x-show="selectedOption || searchTerm.length > 0"
            class="absolute inset-y-0 right-8 flex items-center pr-1 cursor-pointer"
            @click="clearSelection()"
            title="Limpiar selección"
        >
            <svg 
                class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>

        <!-- Dropdown Arrow -->
        <div 
            class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
            @click="toggleDropdown()"
        >
            <svg 
                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <!-- Hidden Input -->
    <input 
        type="hidden" 
        :name="'{{ $name }}'" 
        :value="selectedValue"
    >

    <!-- Dropdown Options -->
    <div 
        x-show="isOpen && filteredOptions.length > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-[999] w-full mt-1 bg-muted border border-border rounded-md shadow-lg max-h-60 overflow-auto"
        style="display: none;"
    >
        <template x-for="(option, index) in filteredOptions" :key="option.id">
            <div 
                @click="selectOption(option)"
                @mouseenter="highlightedIndex = index"
                class="px-4 py-2 cursor-pointer transition-colors duration-150"
                :class="{
                    'bg-card text-card-foreground': highlightedIndex === index,
                    'text-muted-foreground': highlightedIndex !== index
                }"
                x-text="option.name"
            ></div>
        </template>
    </div>

    <!-- No Results -->
    <div 
        x-show="isOpen && filteredOptions.length === 0 && searchTerm.length > 0"
        x-transition
        class="absolute z-50 w-full mt-1 bg-muted border border-border rounded-md shadow-lg"
        style="display: none;"
    >
        <div class="px-4 py-2 text-muted-foreground">
            No se encontraron resultados
        </div>
    </div>
</div>

<script>
function searchableSelect(config) {
    return {
        options: [],
        filteredOptions: [],
        selectedOption: null,
        valueEntangle: config.valueEntangle,
        optionsEntangle: config.optionsEntangle, // ✅ NUEVO
        selectedValue: config.initialValue || '',
        searchTerm: '',
        isOpen: false,
        highlightedIndex: -1,
        placeholder: config.placeholder || 'Selecciona una opción',

        init() {
            // ✅ Inicializar opciones desde entangle
            this.options = this.optionsEntangle || [];
            this.filteredOptions = this.options;

            // Set initial selected option
            if (this.selectedValue) {
                this.updateSelectedOption(this.selectedValue);
            }

            // ✅ Watch para cambios en las opciones desde Livewire
            this.$watch('optionsEntangle', (newOptions) => {
                alert(5);
                console.log('✅ Opciones actualizadas desde Livewire:', newOptions);
                this.options = newOptions || [];
                this.filteredOptions = newOptions || [];
                
                // Revalidar selección actual
                if (this.selectedValue) {
                    const stillExists = this.options.find(option => option.id == this.selectedValue);
                    if (!stillExists) {
                        console.warn('⚠️ Opción seleccionada ya no existe, limpiando...');
                        this.clearSelection();
                    } else {
                        this.updateSelectedOption(this.selectedValue);
                    }
                }
            });

            // ✅ Watch para cambios en el valor seleccionado desde Livewire
            this.$watch('valueEntangle', (value) => {
                console.log('✅ Valor seleccionado actualizado:', value);
                this.selectedValue = value;
                if (value) {
                    this.updateSelectedOption(value);
                } else {
                    this.selectedOption = null;
                    this.searchTerm = '';
                }
            });
        },

        updateSelectedOption(value) {
            this.selectedOption = this.options.find(option => option.id == value);
            if (this.selectedOption) {
                this.searchTerm = this.selectedOption.name;
            }
        },

        openDropdown() {
            this.isOpen = true;
            this.highlightedIndex = -1;
            this.filterOptions();
            this.$nextTick(() => this.$refs.searchInput.focus());
        },

        closeDropdown() {
            this.isOpen = false;
            this.highlightedIndex = -1;
            if (this.selectedOption) {
                this.searchTerm = this.selectedOption.name;
            } else {
                this.searchTerm = '';
                this.filteredOptions = this.options;
            }
        },

        toggleDropdown() {
            if (this.isOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown();
                this.$refs.searchInput.focus();
            }
        },

        filterOptions() {
            if (!this.searchTerm || this.searchTerm.trim() === '') {
                this.filteredOptions = this.options;
            } else {
                this.filteredOptions = this.options.filter(option =>
                    option.name.toLowerCase().includes(this.searchTerm.toLowerCase())
                );
            }
            this.highlightedIndex = this.filteredOptions.length > 0 ? 0 : -1;
        },

        navigateDown() {
            if (this.filteredOptions.length === 0) return;
            
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            
            this.highlightedIndex = this.highlightedIndex < this.filteredOptions.length - 1 
                ? this.highlightedIndex + 1 
                : 0;
            
            this.scrollToHighlighted();
        },

        navigateUp() {
            if (this.filteredOptions.length === 0) return;
            
            if (!this.isOpen) {
                this.openDropdown();
                return;
            }
            
            this.highlightedIndex = this.highlightedIndex > 0 
                ? this.highlightedIndex - 1 
                : this.filteredOptions.length - 1;
            
            this.scrollToHighlighted();
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const dropdown = this.$el.querySelector('[x-show="isOpen && filteredOptions.length > 0"]');
                const highlighted = dropdown?.children[this.highlightedIndex];
                if (highlighted) {
                    highlighted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.filteredOptions[this.highlightedIndex]) {
                this.selectOption(this.filteredOptions[this.highlightedIndex]);
            }
        },

        selectOption(option) {
            console.log('✅ Opción seleccionada:', option);
            this.selectedOption = option;
            this.selectedValue = option.id;
            this.searchTerm = option.name;
            this.closeDropdown();
            
            // ✅ Actualizar Livewire
            this.valueEntangle = option.id;
        },

        clearSelection() {
            console.log('🧹 Limpiando selección');
            this.selectedOption = null;
            this.selectedValue = '';
            this.searchTerm = '';
            this.filteredOptions = this.options;
            this.highlightedIndex = -1;
            
            // ✅ Actualizar Livewire
            this.valueEntangle = '';
            
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        },
    }
}
</script>