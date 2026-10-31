{{--
    Menú lateral, estilo explorador de archivos (DESIGN.md → Barra lateral).
    - Inicio, "Acceso directo" (de cada usuario) y los grupos en árbol con flecha.
    - Los grupos cargan sin expandir. En el menú de usuario cada uno puede activar "Abrir el grupo de la página actual"
      y "Recordar los grupos que dejo abiertos" (usuario_preferencias; lo abierto se guarda en su navegador).
    - Clic derecho en cualquier opción: abrir, abrir en pestaña nueva, agregar / quitar de Acceso directo. En Acceso
      directo el pin quita la opción y se puede arrastrar para ordenar. Se guarda por usuario (SistemaMenuProceso).
    - Escritorio (lg+): fijo a la altura de la pantalla; contraído (iconos) se expande al pasar el mouse; "fijar" lo deja
      expandido. Celular / tablet: barra superior con hamburguesa y el menú flotante, siempre expandido.
    - Buscador: sin tildes ni mayúsculas; filtra accesos y opciones (si coincide un grupo, muestra todas sus opciones).
--}}
@php
    $rutaActual = \Illuminate\Support\Facades\Route::currentRouteName();
    $fila = 'relative flex w-full items-center gap-2.5 rounded-[6px] h-8 text-[13px] transition-colors';
    $activa = 'bg-fog text-ink font-medium before:absolute before:left-0 before:top-2 before:bottom-2 before:w-[3px] before:rounded-full before:bg-carmin';
    $normal = 'text-graphite hover:bg-fog hover:text-ink';
@endphp
<div x-data="{
    isPinned: $persist(false).as('menu_pinned'),
    hovered: false,
    esMovil: false,
    mobileOpen: false,
    search: '',
    // Grupos abiertos. Por defecto todos sin expandir al cargar; según las preferencias del usuario se abre el de la
    // página actual y/o se recuerdan los que dejó abiertos (en este navegador)
    openMenus: [],
    prefs: @js($preferencias),
    claveAbiertos: 'menu_abiertos_{{ auth()->id() }}',
    accesos: @js($accesos),
    opciones: {},
    rutaActual: @js($rutaActual),
    ctx: { show: false, x: 0, y: 0, ruta: null },
    arrastrando: null,

    init() {
        this.opciones = JSON.parse(document.getElementById('menu-opciones').textContent);
        if (this.prefs.recordar_abiertos) {
            try { this.openMenus = [...new Set([...this.openMenus, ...(JSON.parse(localStorage.getItem(this.claveAbiertos)) || [])])]; } catch (e) {}
        }
        this.$watch('openMenus', v => {
            if (this.prefs.recordar_abiertos) { try { localStorage.setItem(this.claveAbiertos, JSON.stringify(v)); } catch (e) {} }
        });
        const mq = window.matchMedia('(max-width: 1023px)');
        this.esMovil = mq.matches;
        mq.addEventListener('change', e => {
            this.esMovil = e.matches;
            if (!e.matches) this.mobileOpen = false;
        });
        this.$watch('mobileOpen', v => document.body.classList.toggle('overflow-hidden', v));
    },
    get isExpanded() { return this.esMovil || this.isPinned || this.hovered; },
    entrar() { if (!this.esMovil) this.hovered = true; },
    salir() {
        if (this.esMovil || this.ctx.show) return;
        this.hovered = false;
    },
    togglePin() { this.isPinned = !this.isPinned; },
    toggleMenu(title) {
        this.openMenus = this.openMenus.includes(title) ? this.openMenus.filter(m => m !== title) : [...this.openMenus, title];
    },
    isOpen(title) { return this.search !== '' || this.openMenus.includes(title); },
    norm(s) { return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
    coincide(texto) { return this.norm(texto).includes(this.norm(this.search)); },

    // ---- Accesos directos
    get listaAccesos() {
        return this.accesos.map(r => this.opciones[r]).filter(o => o && (this.search === '' || this.coincide(o.title) || this.coincide(o.grupo)));
    },
    esAcceso(ruta) { return this.accesos.includes(ruta); },
    async guardarAccesos(datos) {
        try {
            const r = await fetch(@js(route('sistema.accesos_directos')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(datos),
            });
            const json = await r.json();
            if (!r.ok) throw new Error(json.error || json.message || 'No se pudo guardar.');
            if (json.accesos) this.accesos = json.accesos;
            if (json.preferencias) this.prefs = json.preferencias;
        } catch (e) {
            window.Swal ? Swal.fire({ icon: 'warning', text: e.message, toast: true, position: 'top-end', timer: 4000, showConfirmButton: false }) : alert(e.message);
        }
    },
    async guardarPreferencias() {
        await this.guardarAccesos({ accion: 'preferencias', abrir_activo: this.prefs.abrir_activo, recordar_abiertos: this.prefs.recordar_abiertos });
        if (this.prefs.recordar_abiertos) { try { localStorage.setItem(this.claveAbiertos, JSON.stringify(this.openMenus)); } catch (e) {} }
        else { try { localStorage.removeItem(this.claveAbiertos); } catch (e) {} }
    },
    agregarAcceso(ruta) { this.guardarAccesos({ accion: 'agregar', ruta }); },
    quitarAcceso(ruta) { this.guardarAccesos({ accion: 'quitar', ruta }); },
    soltarEn(ruta) {
        if (!this.arrastrando || this.arrastrando === ruta) return;
        const lista = this.accesos.filter(r => r !== this.arrastrando);
        lista.splice(lista.indexOf(ruta), 0, this.arrastrando);
        this.accesos = lista;
        this.arrastrando = null;
        this.guardarAccesos({ accion: 'ordenar', rutas: lista });
    },

    // ---- Menú contextual (clic derecho)
    abrirCtx(e, ruta) {
        this.ctx = { show: true, ruta, x: Math.min(e.clientX, window.innerWidth - 248), y: Math.min(e.clientY, window.innerHeight - 170) };
    },
    cerrarCtx() { this.ctx.show = false; },
}" @keydown.escape.window="mobileOpen = false; cerrarCtx()" @scroll.window="cerrarCtx()" @resize.window="cerrarCtx()"
    class="lg:sticky lg:top-0 lg:h-screen lg:self-start lg:flex-shrink-0 z-40 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    {{-- Opciones visibles del usuario (para dibujar sus accesos directos y el menú contextual) --}}
    <script type="application/json" id="menu-opciones">@json($opciones, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)</script>

    {{-- Barra superior (celular / tablet) --}}
    <div class="lg:hidden fixed top-0 inset-x-0 h-14 z-40 flex items-center gap-3 px-4 bg-paper border-b border-mist">
        <button type="button" @click="mobileOpen = true" class="p-2 -ml-2 text-ink" aria-label="Abrir menú">
            <i class="fa fa-bars text-lg"></i>
        </button>
        <a href="{{ route('inicio') }}" class="flex items-center gap-2">
            <img src="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('icono') }}" class="w-7 h-7 object-contain" alt="Logo" />
            <span class="font-semibold text-ink" title="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}">{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::siglas() }}</span>
        </a>
    </div>

    {{-- Fondo oscuro detrás del menú flotante --}}
    <div x-show="mobileOpen" x-transition.opacity x-cloak @click="mobileOpen = false" class="lg:hidden fixed inset-0 z-40 bg-black/50"></div>

    <aside x-on:mouseenter="entrar" x-on:mouseleave="salir"
        class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col bg-paper border-r border-mist transition-all duration-300 ease-in-out
               -translate-x-full lg:translate-x-0 lg:static lg:h-full"
        :class="[mobileOpen ? 'translate-x-0 shadow-2xl' : '', isExpanded ? 'lg:w-64' : 'lg:w-16']">

        <div class="px-4 h-14 flex items-center border-b border-mist">
            <div class="flex w-full items-center justify-between">
                <a class="flex items-center gap-3" href="{{ route('inicio') }}">
                    <img src="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('icono') }}" class="w-8 h-8 flex-shrink-0 object-contain" alt="Logo" />
                    <span x-show="isExpanded" class="font-semibold text-ink text-nowrap" title="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}">{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::siglas() }}</span>
                </a>
                <button x-show="isExpanded && !esMovil" x-on:click="togglePin" class="text-steel hover:text-ink"
                    :title="isPinned ? 'Dejar de fijar el menú' : 'Fijar el menú expandido'">
                    <i class="fa" :class="isPinned ? 'fa-thumbtack text-carmin' : 'fa-thumbtack -rotate-45'"></i>
                </button>
                <button x-show="esMovil" x-cloak @click="mobileOpen = false" class="p-1 text-graphite" aria-label="Cerrar menú">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <div x-show="isExpanded" class="p-2 border-b border-mist">
            <div class="relative">
                <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-steel"></i>
                <input type="search" x-model="search" name="search_field_{{ uniqid() }}" placeholder="Buscar en el menú…"
                    autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"
                    class="block w-full h-8 ps-8 text-[13px] border border-mist rounded-[6px] bg-fog text-ink placeholder:text-steel focus:border-carmin focus:ring-[3px] focus:ring-carmin/25" />
            </div>
        </div>

        <nav class="flex-1 min-h-0 py-2 px-2" :class="isExpanded ? 'overflow-y-auto ultra-thin-scroll' : 'overflow-hidden'">
            {{-- Inicio --}}
            <a href="{{ route('inicio') }}" x-show="search === ''" title="Inicio"
                class="{{ $fila }} {{ $rutaActual === 'inicio' ? $activa : $normal }}" :class="isExpanded ? 'px-2' : 'justify-center px-0'">
                <i class="fa fa-house w-5 flex-shrink-0 text-center text-[15px] text-[#E8833A] dark:text-[#F09A55]"></i>
                <span x-show="isExpanded" class="truncate">Inicio</span>
            </a>

            {{-- Acceso directo --}}
            <div class="my-2 border-t border-hairline"></div>
            <p x-show="isExpanded" class="px-2 pb-1 text-[11px] font-medium uppercase tracking-wide text-pewter">Acceso directo</p>
            <template x-for="o in listaAccesos" :key="o.route">
                <a :href="o.url" :title="o.title + ' · ' + o.grupo" draggable="true"
                    @dragstart="arrastrando = o.route" @dragover.prevent @drop.prevent="soltarEn(o.route)"
                    @contextmenu.prevent="abrirCtx($event, o.route)"
                    class="group {{ $fila }}" :class="[o.route === rutaActual ? @js($activa) : @js($normal), isExpanded ? 'px-2' : 'justify-center px-0']">
                    <i class="w-5 flex-shrink-0 text-center text-[14px] text-[var(--c)] dark:text-[var(--cd)]" :class="o.icon"
                        :style="`--c:${o.color[0]};--cd:${o.color[1]}`"></i>
                    <span x-show="isExpanded" class="truncate" x-text="o.title"></span>
                    <button type="button" x-show="isExpanded" @click.prevent.stop="quitarAcceso(o.route)"
                        class="ml-auto px-1 text-[11px] text-steel hover:text-carmin" title="Quitar de Acceso directo">
                        <i class="fa fa-thumbtack"></i>
                    </button>
                </a>
            </template>
            <p x-show="isExpanded && accesos.length === 0 && search === ''" class="px-2 py-1 text-xs text-pewter">
                Clic derecho en una opción del menú → <span class="font-medium text-graphite">Agregar a acceso directo</span>.
            </p>

            {{-- Grupos en árbol --}}
            <div class="my-2 border-t border-hairline"></div>
            @foreach ($menu as $item)
                <div x-data="{
                        titulo: @js($item['title']),
                        hijos: @js(collect($item['children'])->pluck('title')->values()),
                        get coincidePadre() { return search === '' || coincide(this.titulo); },
                        hijoVisible(t) { return this.coincidePadre || coincide(t); },
                        get visible() { return this.coincidePadre || this.hijos.some(h => coincide(h)); },
                        init() {
                            if (prefs.abrir_activo && {{ $item['isActive'] ? 'true' : 'false' }} && !openMenus.includes(this.titulo)) openMenus.push(this.titulo);
                        }
                    }" x-show="visible" style="--c: {{ $item['color'][0] }}; --cd: {{ $item['color'][1] }}">
                    <button type="button" x-on:click="toggleMenu(titulo)"
                        class="{{ $fila }} {{ $item['isActive'] ? 'text-ink font-medium hover:bg-fog' : $normal }}"
                        :class="isExpanded ? 'pl-1 pr-2' : 'justify-center px-0'" title="{{ $item['title'] }}">
                        <i x-show="isExpanded" class="fa fa-chevron-right w-3 text-[9px] text-steel transition-transform duration-150" :class="isOpen(titulo) ? 'rotate-90' : ''"></i>
                        <i class="{{ $item['icon'] }} w-5 flex-shrink-0 text-center text-[15px] text-[var(--c)] dark:text-[var(--cd)]"></i>
                        <span x-show="isExpanded" class="truncate">{{ $item['title'] }}</span>
                    </button>
                    <div x-show="isExpanded && isOpen(titulo)" x-collapse class="pb-1">
                        @php $secciones = collect($item['children'])->groupBy(fn($c) => $c['seccion'] ?? ''); @endphp
                        @foreach ($secciones as $seccion => $opciones)
                            @if ($secciones->count() > 1 && $seccion !== '')
                                <p x-show="@js($opciones->pluck('title')->values()).some(t => hijoVisible(t))"
                                    class="pl-9 pr-2 pt-2 pb-0.5 text-[10.5px] font-medium uppercase tracking-wide text-pewter">{{ $seccion }}</p>
                            @endif
                            @foreach ($opciones as $child)
                                <a href="{{ $child['url'] }}" x-show="hijoVisible(@js($child['title']))"
                                    @contextmenu.prevent="abrirCtx($event, @js($child['route']))"
                                    class="{{ $fila }} pl-9 pr-2 {{ $child['isActive'] ? $activa : $normal }}">
                                    <i class="{{ $child['icon'] }} w-4 flex-shrink-0 text-center text-[12px] text-[var(--c)] opacity-80 dark:text-[var(--cd)]"></i>
                                    <span class="truncate">{{ $child['title'] }}</span>
                                    <i x-show="esAcceso(@js($child['route']))" class="fa fa-thumbtack ml-auto text-[9px] text-steel" title="Está en Acceso directo"></i>
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Modo claro / oscuro --}}
        <div class="border-t border-mist p-2">
            <button @click="darkMode = !darkMode" class="{{ $fila }} {{ $normal }}" :class="isExpanded ? 'px-2' : 'justify-center px-0'">
                <i :class="darkMode ? 'fa fa-moon' : 'fa fa-sun'" class="w-5 text-center"></i>
                <span x-show="isExpanded" x-text="darkMode ? 'Modo oscuro' : 'Modo claro'"></span>
            </button>
        </div>

        {{-- Usuario --}}
        <div class="border-t border-mist p-2">
            <div class="relative">
                <button onclick="toggleUserMenu()" class="w-full flex items-center gap-2 rounded-[6px] p-1.5 text-graphite hover:bg-fog transition-colors"
                    :class="isExpanded ? 'justify-start' : 'justify-center'">
                    <div class="w-8 h-8 flex-shrink-0 rounded-[6px] bg-fog text-ink border border-mist flex items-center justify-center font-semibold text-xs">
                        {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <template x-if="isExpanded">
                        <div class="flex-1 flex items-center justify-between min-w-0">
                            <div class="text-left min-w-0">
                                <div class="font-medium text-[13px] truncate text-ink">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-pewter truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <i id="user-chevron" class="fa fa-chevron-up text-[10px] text-steel transition-transform flex-shrink-0"></i>
                        </div>
                    </template>
                </button>

                <div id="user-dropdown"
                    class="absolute bottom-full left-0 w-full mb-2 bg-paper rounded-[10px] shadow-lg border border-mist opacity-0 invisible transition-all duration-200">
                    <div class="p-1.5">
                        {{-- Preferencias del menú (por defecto: todos los grupos sin expandir) --}}
                        <p class="px-2 pt-1 pb-1 text-[11px] font-medium uppercase tracking-wide text-pewter">Menú</p>
                        <label class="flex cursor-pointer items-start gap-2 rounded-[6px] px-2 py-1.5 text-[13px] text-ink hover:bg-fog">
                            <input type="checkbox" x-model="prefs.abrir_activo" @change="guardarPreferencias()"
                                class="mt-0.5 rounded border-mist text-carmin focus:ring-carmin/25" />
                            <span>Abrir el grupo de la página actual</span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-[6px] px-2 py-1.5 text-[13px] text-ink hover:bg-fog">
                            <input type="checkbox" x-model="prefs.recordar_abiertos" @change="guardarPreferencias()"
                                class="mt-0.5 rounded border-mist text-carmin focus:ring-carmin/25" />
                            <span>Recordar los grupos que dejo abiertos</span>
                        </label>
                        <p class="px-2 pb-1 text-[11px] text-pewter">Sin marcar: todos los grupos cargan sin expandir.</p>
                        <div class="my-1 border-t border-hairline"></div>
                        <a href="{{ route('profile.show') }}" class="{{ $fila }} px-2 {{ $normal }}">
                            <i class="fa fa-gear w-4 text-center"></i><span>Configuración</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="{{ $fila }} px-2 text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-400/10">
                                <i class="fa fa-sign-out-alt w-4 text-center"></i><span>Salir</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Menú contextual (clic derecho en una opción) --}}
    <template x-teleport="body">
        <div x-show="ctx.show" x-cloak x-transition.opacity.duration.100ms @click.outside="cerrarCtx()" @contextmenu.prevent
            class="fixed z-[100] w-60 rounded-[10px] border border-mist bg-paper p-1 shadow-xl font-['Inter',ui-sans-serif,system-ui,sans-serif] text-[13px]"
            :style="`left:${ctx.x}px;top:${ctx.y}px`">
            <template x-if="ctx.ruta && opciones[ctx.ruta]">
                <div>
                    <p class="truncate px-2.5 pt-1 pb-1.5 text-xs text-pewter" x-text="opciones[ctx.ruta].grupo + ' · ' + opciones[ctx.ruta].title"></p>
                    <a :href="opciones[ctx.ruta].url" @click="cerrarCtx()" class="flex items-center gap-2.5 rounded-[6px] px-2.5 h-8 text-ink hover:bg-fog">
                        <i class="fa fa-arrow-up-right-from-square w-4 text-center text-steel"></i> Abrir
                    </a>
                    <a :href="opciones[ctx.ruta].url" target="_blank" @click="cerrarCtx()" class="flex items-center gap-2.5 rounded-[6px] px-2.5 h-8 text-ink hover:bg-fog">
                        <i class="fa fa-window-restore w-4 text-center text-steel"></i> Abrir en una pestaña nueva
                    </a>
                    <div class="my-1 border-t border-hairline"></div>
                    <button type="button" x-show="!esAcceso(ctx.ruta)" @click="agregarAcceso(ctx.ruta); cerrarCtx()"
                        class="flex w-full items-center gap-2.5 rounded-[6px] px-2.5 h-8 text-left text-ink hover:bg-fog">
                        <i class="fa fa-thumbtack w-4 text-center text-carmin"></i> Agregar a acceso directo
                    </button>
                    <button type="button" x-show="esAcceso(ctx.ruta)" @click="quitarAcceso(ctx.ruta); cerrarCtx()"
                        class="flex w-full items-center gap-2.5 rounded-[6px] px-2.5 h-8 text-left text-ink hover:bg-fog">
                        <i class="fa fa-thumbtack w-4 text-center text-steel -rotate-45"></i> Quitar de acceso directo
                    </button>
                </div>
            </template>
        </div>
    </template>

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
