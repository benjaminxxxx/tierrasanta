@props([
    'options' => null,
    'name' => 'searchable_select',
    'searchPlaceholder' => 'Buscar...',
])


<div x-data="searchable({
    options: @js($options),
    entangle: @entangle($attributes->wire('model'))
})" class="mt-2" @click.away="closeDropdown()" @keydown.escape="closeDropdown()">
    <div class="relative">
        <input type="text" x-model="searchTerm" x-ref="searchInput" @input="filterOptions()" @focus="openDropdown()"
            @keydown.arrow-down.prevent="navigateDown()" @keydown.arrow-up.prevent="navigateUp()"
            @keydown.enter.prevent="selectHighlighted()" @keydown.tab="closeDropdown()"
            :placeholder="selectedOption ? selectedOption.name : '{{ $searchPlaceholder }}'"
            class="w-full rounded-lg border border-slate-400 dark:border-0 dark:text-primaryTextDark bg-transparent py-2 px-4 pr-20 outline-none focus:border-primary focus-visible:shadow-none dark:border-form-strokedark dark:bg-gray-700 dark:focus:border-primary focus:ring-0"
            autocomplete="off">

        <!-- Clear Button (X) -->
        <div x-show="selectedOption || searchTerm.length > 0"
            class="absolute inset-y-0 right-8 flex items-center pr-1 cursor-pointer" @click="clearSelection()"
            title="Limpiar selección">
            <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-150"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>

        <!-- Dropdown Arrow -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" @click="toggleDropdown()">
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <!-- Hidden Input for Form Submission -->
    <input type="hidden" :name="'{{ $name }}'" :value="selectedValue"
        {{ $attributes->except(['class', 'placeholder']) }}>

    <!-- Dropdown Options -->
    <div x-show="isOpen && filteredOptions.length > 0" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-[999] mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto"
        style="display: none;">
        <template x-for="(option, index) in filteredOptions" :key="option.id">
            <div @click="selectOption(option)" @mouseenter="highlightedIndex = index"
                class="px-4 py-2 cursor-pointer transition-colors duration-150"
                :class="{
                    'bg-primary text-white': highlightedIndex === index,
                    'text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-600': highlightedIndex !==
                        index
                }"
                x-text="option.name"></div>
        </template>
    </div>

    <!-- No Results Message -->
    <div x-show="isOpen && filteredOptions.length === 0 && searchTerm.length > 0"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg"
        style="display: none;">
        <div class="px-4 py-2 text-gray-500 dark:text-gray-400">
            No se encontraron resultados
        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('searchable', (config) => ({
            options: config.options,
            filteredOptions: [],
            searchTerm: '',
            selectedOption: null,
            entangle: config.entangle,
            isOpen: false,
            selectedValue: config.initialValue || '',
            highlightedIndex: -1,
            placeholder: config.placeholder || 'Selecciona una opción',
            init() {
                if (this.entangle) {
                    const match = this.options.find(opt => opt.id == this.entangle);
                    if (match) {
                        this.selectedOption = match;
                        this.selectedValue = match.id;
                        this.searchTerm = match.name;
                    }
                }

                this.$watch('options', (value) => {
                    if (this.entangle) {
                        const match = this.options.find(opt => opt.id == this.entangle);
                        if (match) {
                            this.selectedOption = match;
                            this.selectedValue = match.id;
                            this.searchTerm = match.name;
                        }
                    } else {
                        this.selectedOption = null;
                        this.selectedValue = '';
                        this.searchTerm = '';
                    }
                });

                this.$watch('entangle', (value) => {
                    if (!value) {
                        this.selectedOption = null;
                        this.selectedValue = '';
                        this.searchTerm = '';
                    } else {
                        const match = this.options.find(opt => opt.id == value);
                        if (match) {
                            this.selectedOption = match;
                            this.selectedValue = match.id;
                            this.searchTerm = match.name;
                        }
                    }
                });
            },
            /*init() { VERSION FUNCIONAL EN CASO EL CODIGO NUEVO DE PROBLEMAS
                this.$watch('options', (value) => {
                   
                    this.selectedOption = null;
                    this.selectedValue = '';
                    this.searchTerm = '';
                    this.entangle = null;
                });
                this.$watch('entangle', (value) => {
                    if (!value) {
                        this.selectedOption = null;
                        this.selectedValue = '';
                        this.searchTerm = '';
                    } else {
                        // Si tienes opciones cargadas, intenta establecer automáticamente el seleccionado
                        const match = this.options.find(opt => opt.id == value);
                        if (match) {
                            this.selectedOption = match;
                            this.selectedValue = match.id;
                            this.searchTerm = match.name;
                        }
                    }
                });
            },*/
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
            openDropdown() {
                this.isOpen = true;
                this.highlightedIndex = -1;
                this.filterOptions();

                // Asegúrate de volver a enfocar
                this.$nextTick(() => this.$refs.searchInput.focus());
            },
            closeDropdown() {
                this.isOpen = false;
                this.highlightedIndex = -1;
                // Reset search term to selected option name or empty
                if (this.selectedOption) {
                    this.searchTerm = this.selectedOption.name;
                } else {
                    this.searchTerm = '';
                    this.filteredOptions = this.options; // Restaurar todas las opciones cuando no hay selección
                }
            },
            clearSelection() {
                this.selectedOption = null;
                this.selectedValue = '';
                this.searchTerm = '';
                this.entangle = null;
                this.filteredOptions = this.options; // Restaurar todas las opciones
                this.highlightedIndex = -1;

                // Enfocar el input después de limpiar
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                });
            },
            navigateDown() {
                if (this.filteredOptions.length === 0) return;

                if (!this.isOpen) {
                    this.openDropdown();
                    return;
                }

                this.highlightedIndex = this.highlightedIndex < this.filteredOptions.length - 1 ?
                    this.highlightedIndex + 1 :
                    0;
            },

            navigateUp() {
                if (this.filteredOptions.length === 0) return;

                if (!this.isOpen) {
                    this.openDropdown();
                    return;
                }

                this.highlightedIndex = this.highlightedIndex > 0 ?
                    this.highlightedIndex - 1 :
                    this.filteredOptions.length - 1;
            },

            selectHighlighted() {
                if (this.highlightedIndex >= 0 && this.filteredOptions[this.highlightedIndex]) {
                    this.selectOption(this.filteredOptions[this.highlightedIndex]);
                }
            },
            selectOption(option) {
                this.selectedOption = option;
                this.selectedValue = option.id;
                this.searchTerm = option.name;
                this.closeDropdown();
                this.entangle = option.id;
            },

            toggleDropdown() {
                if (this.isOpen) {
                    this.closeDropdown();
                } else {
                    this.openDropdown();
                    this.$refs.searchInput.focus();
                }
            }
        }))
    </script>
@endscript
