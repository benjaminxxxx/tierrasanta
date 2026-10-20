{{--
    Menú lateral.
    - Escritorio (lg+): aside fijo a la altura de la pantalla (sticky): no se va al bajar en páginas largas; su propia
      lista hace scroll. Contraído (iconos) se expande al pasar el mouse; "fijar" lo deja expandido.
    - Celular / tablet (< lg): barra superior con hamburguesa; el aside sale flotando desde la izquierda sobre la página,
      siempre expandido. "Fijar" no aplica.
    - Buscador: sin tildes ni mayúsculas. Si coincide el menú, muestra todos sus submenús; si coinciden solo algunos
      submenús, muestra únicamente esos.
--}}
<div x-data="{
    isPinned: $persist(false).as('menu_pinned'),
    hovered: false,
    esMovil: false,
    mobileOpen: false,
    search: '',
    openMenus: [],

    init() {
        const mq = window.matchMedia('(max-width: 1023px)');
        this.esMovil = mq.matches;
        mq.addEventListener('change', e => {
            this.esMovil = e.matches;
            if (!e.matches) this.mobileOpen = false;
        });
        this.$watch('mobileOpen', v => document.body.classList.toggle('overflow-hidden', v));
    },
    get isExpanded() {
        return this.esMovil || this.isPinned || this.hovered;
    },
    entrar() { if (!this.esMovil) this.hovered = true; },
    salir() {
        if (this.esMovil) return;
        this.hovered = false;
        if (!this.isPinned && !this.search) this.openMenus = [];
    },
    togglePin() { this.isPinned = !this.isPinned; },
    toggleMenu(title) {
        this.openMenus = this.openMenus.includes(title)
            ? this.openMenus.filter(m => m !== title)
            : [...this.openMenus, title];
    },
    isOpen(title) { return this.search !== '' || this.openMenus.includes(title); },
    norm(s) { return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
    coincide(texto) { return this.norm(texto).includes(this.norm(this.search)); },
}" @keydown.escape.window="mobileOpen = false"
    class="lg:sticky lg:top-0 lg:h-screen lg:self-start lg:flex-shrink-0 z-40">

    {{-- Barra superior (celular / tablet) --}}
    <div class="lg:hidden fixed top-0 inset-x-0 h-14 z-40 flex items-center gap-3 px-4 bg-card border-b border-border">
        <button type="button" @click="mobileOpen = true" class="p-2 -ml-2 text-card-foreground" aria-label="Abrir menú">
            <i class="fa fa-bars text-lg"></i>
        </button>
        <a href="{{ route('inicio') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/logo/logotipo.svg') }}" class="w-7 h-7" alt="Logo" />
            <span class="font-semibold text-card-foreground">THS</span>
        </a>
    </div>

    {{-- Fondo oscuro detrás del menú flotante --}}
    <div x-show="mobileOpen" x-transition.opacity x-cloak @click="mobileOpen = false"
        class="lg:hidden fixed inset-0 z-40 bg-black/50"></div>

    <aside x-on:mouseenter="entrar" x-on:mouseleave="salir"
        class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-card border-r border-border transition-all duration-300 ease-in-out
               -translate-x-full lg:translate-x-0 lg:static lg:h-full"
        :class="[mobileOpen ? 'translate-x-0 shadow-2xl' : '', isExpanded ? 'lg:w-64' : 'lg:w-16']">

        <div class="p-4 border-b border-border">
            <div class="flex items-center justify-between">
                <a class="flex items-center space-x-3" href="{{ route('inicio') }}">
                    <img src="{{ asset('images/logo/logotipo.svg') }}" class="w-8 h-8 flex-shrink-0" alt="Logo" />
                    <span x-show="isExpanded" class="font-semibold text-gray-700 dark:text-white text-nowrap">THS</span>
                </a>
                {{-- Fijar: solo en escritorio --}}
                <button x-show="isExpanded && !esMovil" x-on:click="togglePin" class="text-gray-600 dark:text-white"
                    :title="isPinned ? 'Dejar de fijar el menú' : 'Fijar el menú expandido'">
                    <i class="fa" :class="isPinned ? 'fa-times-circle text-blue-500' : 'fa-thumb-tack'"></i>
                </button>
                <button x-show="esMovil" x-cloak @click="mobileOpen = false" class="p-1 text-gray-600 dark:text-white" aria-label="Cerrar menú">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <div x-show="isExpanded" class="p-2 border-b border-border">
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="fa fa-search text-muted-foreground text-sm"></i>
                </div>
                <input type="search" x-model="search" name="search_field_{{ uniqid() }}"
                    placeholder="Buscar menú..." autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
                    class="block w-full h-10 ps-10 text-sm border border-border rounded-lg bg-muted text-muted-foreground focus:ring-blue-500" />
            </div>
        </div>

        <nav class="flex-1 min-h-0 py-4 space-y-1 px-2"
            :class="isExpanded ? 'overflow-y-auto ultra-thin-scroll' : 'overflow-hidden'">
            @foreach ($menu as $item)
                <div x-data="{
                        titulo: @js($item['title']),
                        hijos: @js(collect($item['children'])->pluck('title')->values()),
                        get coincidePadre() { return search === '' || coincide(this.titulo); },
                        hijoVisible(t) { return this.coincidePadre || coincide(t); },
                        get visible() { return this.coincidePadre || this.hijos.some(h => coincide(h)); },
                        init() {
                            if ({{ $item['isActive'] ? 'true' : 'false' }} && !openMenus.includes(this.titulo)) openMenus.push(this.titulo);
                        }
                    }" x-show="visible">
                    <button type="button" x-on:click="toggleMenu(titulo)"
                        class="rounded w-full h-10 flex items-center transition-all duration-200 {{ $item['isActive'] ? 'bg-muted text-card-foreground' : 'text-muted-foreground hover:bg-muted' }}"
                        :class="isExpanded ? 'justify-start px-3' : 'justify-center px-0'" title="{{ $item['title'] }}">
                        <i class="{{ $item['icon'] }} h-5 w-5 flex-shrink-0"></i>
                        <template x-if="isExpanded">
                            <div class="flex items-center ml-3 truncate w-full">
                                <span>{{ $item['title'] }}</span>
                                <i class="fa fa-chevron-right ml-auto h-4 w-4 transform transition-transform duration-200"
                                    :class="isOpen(titulo) ? 'rotate-90' : ''"></i>
                            </div>
                        </template>
                    </button>
                    <div x-show="isExpanded && isOpen(titulo)">
                        @foreach ($item['children'] as $child)
                            <a href="{{ $child['url'] }}" x-show="hijoVisible(@js($child['title']))"
                                class="block pl-12 pr-4 py-1 text-sm transition {{ $child['isActive'] ? 'font-semibold text-card-foreground' : 'text-muted-foreground hover:text-card-foreground' }}">
                                {{ $child['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <!-- BOTÓN DE MODO CLARO/OSCURO -->
        <div class="border-t border-border p-4">
            <button @click="darkMode = !darkMode"
                class="w-full flex items-center p-2 rounded-lg text-muted-foreground hover:bg-muted transition-colors"
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
                    class="w-full flex items-center p-2 rounded-lg text-muted-foreground hover:bg-muted transition-colors"
                    :class="isExpanded ? 'justify-start' : 'justify-center'">
                    <div class="w-8 h-8 flex-shrink-0 bg-gray-300 text-gray-800 dark:bg-gray-600 dark:text-white rounded-lg flex items-center justify-center font-semibold text-sm">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <template x-if="isExpanded">
                        <div class="flex-1 flex items-center justify-between ml-2">
                            <div class="text-left">
                                <div class="font-semibold text-sm truncate text-gray-900 dark:text-white">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-300 truncate">Empleado</div>
                            </div>
                            <i id="user-chevron" class="fa fa-chevron-up transition-transform flex-shrink-0"></i>
                        </div>
                    </template>
                </button>

                <div id="user-dropdown"
                    class="absolute bottom-full left-0 w-full mb-2 bg-card rounded-lg shadow-lg border border-border opacity-0 invisible transition-all duration-200">
                    <div class="p-2">
                        <div class="px-3 py-2 border-b border-border">
                            <div class="font-semibold text-sm text-card-foreground">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-card-foreground">{{ Auth::user()->email }}</div>
                        </div>
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
    </aside>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-dropdown');
            const chevron = document.getElementById('user-chevron');
            const abrir = dropdown.classList.contains('opacity-0');
            dropdown.classList.toggle('opacity-0', !abrir);
            dropdown.classList.toggle('invisible', !abrir);
            dropdown.classList.toggle('opacity-100', abrir);
            dropdown.classList.toggle('visible', abrir);
            chevron?.classList.toggle('fa-chevron-up', !abrir);
            chevron?.classList.toggle('fa-chevron-down', abrir);
        }

        document.addEventListener('click', function(event) {
            if (event.target.closest('#user-dropdown') || event.target.closest('button[onclick="toggleUserMenu()"]')) return;
            const dropdown = document.getElementById('user-dropdown');
            const chevron = document.getElementById('user-chevron');
            dropdown?.classList.add('opacity-0', 'invisible');
            dropdown?.classList.remove('opacity-100', 'visible');
            chevron?.classList.remove('fa-chevron-down');
            chevron?.classList.add('fa-chevron-up');
        });
    </script>
</div>
