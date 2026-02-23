<div x-data="{
    isPinned: $persist(false).as('menu_pinned'),
    _isHovered: false,
    search: '',
    openMenus: [],

    get isExpanded() {
        return this.isPinned || this._isHovered;
    },

    handleMouseEnter() {
        this._isHovered = true;
    },

    handleMouseLeave() {
        this._isHovered = false;
        if (!this.isPinned && !this.search) {
            this.openMenus = [];
        }
    },

    togglePin() {
        this.isPinned = !this.isPinned;
    },

    toggleMenu(title) {
        if (this.openMenus.includes(title)) {
            this.openMenus = this.openMenus.filter(m => m !== title);
        } else {
            this.openMenus.push(title);
        }
    },

    isOpen(title) {
        return this.openMenus.includes(title);
    },

    shouldShow(title, children) {
        if (!this.search) return true;
        const s = this.search.toLowerCase();
        const match = title.toLowerCase().includes(s) ||
            children.some(child => child.title.toLowerCase().includes(s));

        // Si hay match por búsqueda, forzamos a que el menú se abra
        if (match && !this.openMenus.includes(title)) {
            this.openMenus.push(title);
        }
        return match;
    }
}" x-on:mouseenter="handleMouseEnter" x-on:mouseleave="handleMouseLeave"
    class="relative h-screen bg-card border-r border-border transition-all duration-300 ease-in-out flex flex-col"
    :class="isExpanded ? 'w-64' : 'w-16'">

    <div class="p-4 border-b border-border">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div :class="isExpanded ? 'w-0 opacity-0' : 'w-8 opacity-100'" class="transition-all duration-300">
                    <img src="{{ asset('images/logo/logotipo.svg') }}" class="w-8 h-8" alt="Logo" />
                </div>
                <div :class="isExpanded ? 'w-auto opacity-100' : 'w-0 opacity-0'"
                    class="transition-all duration-300 overflow-hidden text-nowrap">
                    <div class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo/logotipo.svg') }}" class="w-8 h-8" alt="Logo" />
                        <span class="font-semibold text-gray-700 dark:text-white">THS</span>
                    </div>
                </div>
            </div>
            <button x-show="isExpanded" x-on:click="togglePin" class="text-gray-600 dark:text-white">
                <i class="fa" :class="isPinned ? 'fa-times-circle text-blue-500' : 'fa-thumb-tack'"></i>
            </button>
        </div>
    </div>

    <div x-show="isExpanded" x-transition class="p-2 border-b border-border">
        <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-muted-foreground" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                </svg>
            </div>
            <input type="search" id="default-search" x-model="search" name="search_field_{{ uniqid() }}"
                placeholder="Buscar menú..." autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
                class="block w-full h-10 ps-10 text-sm border border-border rounded-lg bg-muted text-muted-foreground focus:ring-blue-500 " />

        </div>
    </div>

    <nav class="flex-1 py-4 space-y-1 px-2"
        :class="isExpanded ? 'overflow-y-auto ultra-thin-scroll' : 'overflow-hidden'">
        @foreach ($menu as $item)
            <div x-show="shouldShow('{{ $item['title'] }}', {{ json_encode($item['children']) }})"
                x-data="{
                    title: '{{ $item['title'] }}',
                    isActive: {{ $item['isActive'] ? 'true' : 'false' }},
                    init() {
                        if (this.isActive) {
                            if (!openMenus.includes(this.title)) openMenus.push(this.title);
                        }
                    }
                }">

                <x-nav-link-parent :name="$item['title']" :logo="$item['icon']" :text="$item['title']" :active="$item['isActive']">
                    @foreach ($item['children'] as $child)
                        <x-nav-link-child
                            x-show="search === '' || '{{ strtolower($child['title']) }}'.includes(search.toLowerCase())"
                            :href="$child['url']" :active="$child['isActive']">
                            {{ $child['title'] }}
                        </x-nav-link-child>
                    @endforeach
                </x-nav-link-parent>
            </div>
        @endforeach
    </nav>

    <!-- BOTÓN DE MODO CLARO/OSCURO -->
    <div class="border-t border-border p-4">
        <button @click="darkMode = !darkMode"
            class="w-full flex items-center p-2 rounded-lg 
               text-muted-foreground hover:bg-muted   transition-colors"
            :class="isExpanded ? 'justify-start' : 'justify-center'">

            <i :class="darkMode ? 'fa fa-moon' : 'fa fa-sun'" class="h-5 w-5"></i>

            <template x-if="isExpanded">
                <span class="ml-3" x-text="darkMode ? 'Modo Oscuro' : 'Modo Claro'"></span>
            </template>
        </button>
    </div>




    <!-- MENÚ DE USUARIO AL FINAL -->
    <div class="border-t border-border p-4">
        <div class="relative">
            <button onclick="toggleUserMenu()"
                class="w-full flex items-center p-2 rounded-lg 
           text-muted-foreground hover:bg-muted   transition-colors"
                :class="isExpanded ? 'justify-start' : 'justify-center'">

                <!-- Avatar -->
                <div
                    class="w-8 h-8 flex-shrink-0 
               bg-gray-300 text-gray-800 
               dark:bg-gray-600 dark:text-white 
               rounded-lg flex items-center justify-center font-semibold text-sm">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>

                <!-- Info solo si el menú está expandido -->
                <template x-if="isExpanded">
                    <div class="flex-1 flex items-center justify-between ml-2">
                        <div class="text-left">
                            <div class="font-semibold text-sm truncate text-gray-900 dark:text-white">
                                {{ Auth::user()->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-300 truncate">Empleado</div>
                        </div>
                        <i id="user-chevron" class="fa fa-chevron-up transition-transform flex-shrink-0"></i>
                    </div>
                </template>
            </button>


            <!-- Dropdown Menu -->
            <div id="user-dropdown"
                class="absolute bottom-full left-0 w-full mb-2 bg-card rounded-lg shadow-lg border border-border opacity-0 invisible transition-all duration-200">
                <div class="p-2">
                    <!-- Información del usuario -->
                    <div class="px-3 py-2 border-b border-border">
                        <div class="font-semibold text-sm text-card-foreground">{{ Auth::user()->name }}
                        </div>
                        <div class="text-xs text-card-foreground">{{ Auth::user()->email }}</div>
                    </div>

                    <!-- Opciones -->
                    <a href="{{ route('profile.show') }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-muted-foreground hover:bg-muted rounded-md">
                        <i class="fa fa-cogs w-4"></i>
                        <span>Configuración</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md">
                            <i class="fa fa-sign-out-alt w-4"></i>
                            <span>Salir</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // User menu toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-dropdown');
            const chevron = document.getElementById('user-chevron');

            if (dropdown.classList.contains('opacity-0')) {
                dropdown.classList.remove('opacity-0', 'invisible');
                dropdown.classList.add('opacity-100', 'visible');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            } else {
                dropdown.classList.add('opacity-0', 'invisible');
                dropdown.classList.remove('opacity-100', 'visible');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = event.target.closest('#user-dropdown') ||
                event.target.closest('button[onclick="toggleUserMenu()"]');

            if (!userMenu) {
                const dropdown = document.getElementById('user-dropdown');
                const chevron = document.getElementById('user-chevron');

                if (dropdown) {
                    dropdown.classList.add('opacity-0', 'invisible');
                    dropdown.classList.remove('opacity-100', 'visible');
                }

                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            }
        });
    </script>
</div>
