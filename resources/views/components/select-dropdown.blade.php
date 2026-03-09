<div x-data="selectSearch({
    source: '{{ $source }}',
    model: '{{ $attributes->wire('model')->value() }}'
})" class="relative" @click.away="closeDropdown()" @keydown.escape="closeDropdown()">

    <div wire:ignore class="relative">

        <x-input type="text" x-model="search" x-ref="searchInput" @input.debounce.250ms="buscar" @focus="handleFocus()"
            @keydown.arrow-down.prevent="navigateDown()" @keydown.arrow-up.prevent="navigateUp()"
            @keydown.enter.prevent="selectHighlighted()" @keydown.tab="closeDropdown()"
            {{ $attributes->except('wire:model', 'source') }} />

        <div x-show="search.length > 0" class="absolute inset-y-0 right-8 flex items-center pr-1 cursor-pointer"
            @click="clearSelection()" title="Limpiar selección"> <svg
                class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-150"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg> </div>

    </div>

    <div x-show="loading" class="text-sm text-muted-foreground">
        cargando...
    </div>

    <ul x-show="isOpen && results.length"
        class="border border-border mt-1 bg-muted absolute w-full z-10 max-h-60 overflow-auto">

        <template x-for="(item,index) in results" :key="item.id">

            <li @click="select(item)" @mouseenter="highlightedIndex=index" class="p-2 cursor-pointer"
                :class="{
                    'bg-card text-card-foreground': highlightedIndex === index,
                    'hover:bg-card': highlightedIndex !== index
                }"
                x-text="item.name"></li>

        </template>

    </ul>

    <div x-show="isOpen && results.length===0 && search.length>0"
        class="border mt-1 p-2 bg-muted text-muted-foreground">
        No se encontraron resultados
    </div>

</div>
@script
    <script>
        Alpine.data('selectSearch', (config) => ({

            source: config.source,
            model: config.model,

            search: '',
            results: [],
            loading: false,

            isOpen: false,
            highlightedIndex: -1,
            init() {
                this.$watch(
                    () => this.$wire[this.model],
                    (value) => {
                        console.log(value);
                        if (value === null || value === undefined) {
                            this.search = ''
                            this.results = []
                            this.highlightedIndex = -1
                        }
                    }
                )
            },

            async buscar() {

                if (this.search.length == 0) {
                    this.clearSelection();
                    return
                }
                if (this.search.length < 2) {
                    this.results = []
                    this.isOpen = false
                    return
                }

                this.loading = true

                try {

                    this.results = await this.$wire[this.source](this.search)

                    this.highlightedIndex = this.results.length ? 0 : -1

                    this.isOpen = true // ← IMPORTANTE

                } catch (e) {

                    console.error(e)

                }

                this.loading = false

            },

            openDropdown() {

                this.isOpen = true

            },
            handleFocus() {

                this.openDropdown()

                if (this.search.length >= 2 && this.results.length === 0) {
                    this.buscar()
                }

            },
            closeDropdown() {

                this.isOpen = false
                this.highlightedIndex = -1

            },

            navigateDown() {

                if (!this.isOpen) {

                    this.openDropdown()
                    return

                }

                if (!this.results.length) return

                this.highlightedIndex =
                    this.highlightedIndex < this.results.length - 1 ?
                    this.highlightedIndex + 1 :
                    0

            },

            navigateUp() {

                if (!this.isOpen) {

                    this.openDropdown()
                    return

                }

                if (!this.results.length) return

                this.highlightedIndex =
                    this.highlightedIndex > 0 ?
                    this.highlightedIndex - 1 :
                    this.results.length - 1

            },

            selectHighlighted() {

                if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {

                    this.select(this.results[this.highlightedIndex])

                }

            },

            select(item) {

                this.search = item.name
                this.results = []
                this.closeDropdown()

                this.$wire.set(this.model, item.id)

            },

            clearSelection() {

                this.search = ''
                this.results = []
                this.highlightedIndex = -1

                this.$wire.set(this.model, null)

                this.$nextTick(() => {
                    this.$refs.searchInput.focus()
                })

            }

        }))
    </script>
@endscript
