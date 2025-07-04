<aside id="sidebar" class="sidebar flex flex-col">
    <!-- SIDEBAR HEADER CON LOGO -->
    <div class="buton-on-sidebar flex items-center justify-between m-auto p-2 border-b border-gray-700">
        <div class="flex items-center w-full justify-center">
            <!-- Logo siempre visible -->
            <div class="hidden-on-expanded flex items-center justify-center max-w-12">
                <img src="{{ asset('images/logo/logotipo.svg') }}" class="w-full" alt="Logo" />
            </div>
            <div class="px-3 menu-text">
                <img src="{{ asset('images/logo/logo-horizontal-color.svg') }}" class="w-full" alt="Logo" />
            </div>
        </div>

        <!-- Botón hamburguesa solo en mobile -->
        <button id="hamburger-btn" class="block lg:hidden text-white">
            <i class="fa fa-times text-xl"></i>
        </button>
    </div>

    <!-- SIDEBAR CONTENT -->
    <div class="flex flex-col flex-1 ultra-thin-scroll">
        <!-- Sidebar Menu -->
        <nav class="px-4 py-4 lg:px-2 flex-1" x-data="{ selected: $persist('Dashboard') }">
            <!-- Menu Group -->
            <div>

                <ul class="mb-6 flex flex-col gap-1.5">
                    @hasanyrole('Administrador|Super Admin')
                    <x-nav-link-parent name="sectorPlanilla" :active="request()->routeIs(['planilla.asistencia'])"
                        logo='fa fa-table' text="Planilla">
                        <x-nav-link-child href="{{ route('planilla.asistencia') }}"
                            :active="request()->routeIs('planilla.asistencia')">
                            Asistencia
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('planilla.blanco') }}"
                            :active="request()->routeIs('planilla.blanco')">
                            Blanco
                        </x-nav-link-child>
                    </x-nav-link-parent>


                    <x-nav-link-parent name="sectorEmpleado" :active="request()->routeIs(['empleados', 'empleados.asignacion_familiar'])" logo='fa fa-users' text="Empleado">
                        <x-nav-link-child href="{{ route('empleados') }}" :active="request()->routeIs('empleados')">
                            Lista de Empleados
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('empleados.asignacion_familiar') }}"
                            :active="request()->routeIs('empleados.asignacion_familiar')">
                            Asignación Familiar
                        </x-nav-link-child>
                    </x-nav-link-parent>
                    @endhasanyrole

                    @canany(['Usuarios Administrar', 'Roles'])
                        <x-nav-link-parent name="sectorSistema" :active="request()->routeIs(['usuarios', 'roles_permisos'])"
                            logo='fas fa-palette' text="Sistema">

                            @can('Usuarios Administrar')
                                <x-nav-link-child href="{{ route('usuarios') }}" :active="request()->routeIs('usuarios')">
                                    Usuarios
                                </x-nav-link-child>
                            @endcan

                            @can('Roles')
                                <x-nav-link-child href="{{ route('roles_permisos') }}"
                                    :active="request()->routeIs('roles_permisos')">
                                    Roles y Permisos
                                </x-nav-link-child>
                            @endcan

                        </x-nav-link-parent>
                    @endcanany

                    @hasanyrole('Administrador|Super Admin')
                    <x-nav-link-parent name="sectorCuadrilla" :active="request()->routeIs(['cuadrilla.grupos', 'cuadrilla.cuadrilleros'])" logo='fas fa-hard-hat' text="Cuadrilla">
                        <x-nav-link-child href="{{ route('cuadrilla.cuadrilleros') }}"
                            :active="request()->routeIs('cuadrilla.cuadrilleros')">
                            Cuadrilleros
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('cuadrilla.grupos') }}"
                            :active="request()->routeIs('cuadrilla.grupos')">
                            Grupos
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorCampo" :active="request()->routeIs(['campo.mapa', 'campo.riego', 'campo.campos', 'campo.siembra'])" logo="fa fa-leaf" text="Campo">
                        <x-nav-link-child href="{{ route('campo.mapa') }}" :active="request()->routeIs('campo.mapa')">
                            Mapa
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('campo.riego') }}" :active="request()->routeIs('campo.riego')">
                            Riego
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('campo.campos') }}"
                            :active="request()->routeIs('campo.campos')">
                            Campos
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('campo.siembra') }}"
                            :active="request()->routeIs('campo.siembra')">
                            Siembras
                        </x-nav-link-child>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorCampanias" :active="request()->routeIs(['campo.campania', 'campanias'])" logo="fa fa-flag" text="Campañas">
                        <x-nav-link-child href="{{ route('campanias') }}" :active="request()->routeIs('campanias')">
                            Todas las campañas
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('campo.campania') }}"
                            :active="request()->routeIs('campo.campania')">
                            Campañas por campo
                        </x-nav-link-child>
                    </x-nav-link-parent>
                    @endhasanyrole
                    @canany(['Cochinilla Administrar', 'Cochinilla Entregar', 'Cochinilla Facturar'])
                                    <x-nav-link-parent name="sectorCochinilla" :active="request()->routeIs([
                            'cochinilla.ingreso',
                            'cochinilla.venteado',
                            'cochinilla.filtrado',
                            'cochinilla.cosecha_mamas',
                            'cochinilla.infestacion',
                            'cochinilla.ventas'
                        ])" logo="fa fa-bug" text="Cochinilla">
                                        <x-nav-link-child href="{{ route('cochinilla.ingreso') }}"
                                            :active="request()->routeIs('cochinilla.ingreso')">
                                            Ingreso
                                        </x-nav-link-child>
                                        <x-nav-link-child href="{{ route('cochinilla.venteado') }}"
                                            :active="request()->routeIs('cochinilla.venteado')">
                                            Venteado
                                        </x-nav-link-child>
                                        <x-nav-link-child href="{{ route('cochinilla.filtrado') }}"
                                            :active="request()->routeIs('cochinilla.filtrado')">
                                            Filtrado
                                        </x-nav-link-child>
                                        <x-nav-link-child href="{{ route('cochinilla.cosecha_mamas') }}"
                                            :active="request()->routeIs('cochinilla.cosecha_mamas')">
                                            Cosecha Mamas
                                        </x-nav-link-child>
                                        <x-nav-link-child href="{{ route('cochinilla.infestacion') }}"
                                            :active="request()->routeIs('cochinilla.infestacion')">
                                            Infestación
                                        </x-nav-link-child>
                                        <x-nav-link-child href="{{ route('cochinilla.ventas') }}"
                                            :active="request()->routeIs('cochinilla.ventas')">
                                            Venta
                                        </x-nav-link-child>
                                    </x-nav-link-parent>
                    @endcanany
                    @hasanyrole('Administrador|Super Admin')

                    <x-nav-link-parent name="sectorReporteCampo" :active="request()->routeIs([
        'reporte_campo.poblacion_plantas',
        'reporte_campo.evaluacion_brotes',
        'reporte_campo.evaluacion_infestacion_cosecha',
        'reporte_campo.evaluacion_proyeccion_rendimiento_poda',
    ])" logo="fa fa-file"
                        text="Evaluación de Campo">
                        <x-nav-link-child href="{{ route('reporte_campo.poblacion_plantas') }}"
                            :active="request()->routeIs('reporte_campo.poblacion_plantas')">
                            Población Plantas
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte_campo.evaluacion_brotes') }}"
                            :active="request()->routeIs('reporte_campo.evaluacion_brotes')">
                            Brotes x Piso
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte_campo.evaluacion_infestacion_cosecha') }}"
                            :active="request()->routeIs('reporte_campo.evaluacion_infestacion_cosecha')">
                            Infestación Cosecha
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte_campo.evaluacion_proyeccion_rendimiento_poda') }}"
                            :active="request()->routeIs('reporte_campo.evaluacion_proyeccion_rendimiento_poda')">
                            Proyección Rendimiento Poda
                        </x-nav-link-child>
                    </x-nav-link-parent>


                    <x-nav-link-parent name="sectorFdm" :active="request()->routeIs(['fdm.costos_generales'])"
                        logo="fa fa-coins" text="FDM">
                        <x-nav-link-child href="{{ route('fdm.costos_generales') }}"
                            :active="request()->routeIs('fdm.costos_generales')">
                            Costos Generales FDM
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorReportes" :active="request()->routeIs([
        'reporte.reporte_diario',
        'reporte.reporte_diario_riego',
        'cuadrilla.asistencia',
        'productividad.avance',
        'reporte.actividades_diarias'
    ])"
                        logo="fa fa-database" text="Reporte Diario">
                        <x-nav-link-child href="{{ route('reporte.actividades_diarias') }}"
                            :active="request()->routeIs('reporte.actividades_diarias')">
                            Actividades Diarias
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte.reporte_diario') }}"
                            :active="request()->routeIs('reporte.reporte_diario')">
                            Planilla
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte.reporte_diario_riego') }}"
                            :active="request()->routeIs('reporte.reporte_diario_riego')">
                            Regadores
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('cuadrilla.asistencia') }}"
                            :active="request()->routeIs('cuadrilla.asistencia')">
                            Cuadrilleros
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('productividad.avance') }}"
                            :active="request()->routeIs('productividad.avance')">
                            Avance de Productividad
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorReporte" :active="request()->routeIs(['reporte.pago_cuadrilla', 'reporte.resumen_planilla'])" logo="fas fa-file-alt" text="Reporte General">
                        <x-nav-link-child href="{{ route('reporte.pago_cuadrilla') }}"
                            :active="request()->routeIs('reporte.pago_cuadrilla')">
                            Pago de cuadrilla
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('reporte.resumen_planilla') }}"
                            :active="request()->routeIs('reporte.resumen_planilla')">
                            Actividades de la Planilla
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorProducto" :active="request()->routeIs(['productos.index', 'nutrientes.index', 'tabla_concentracion.index'])" logo="fa fa-box"
                        text="Producto y Nutrientes">
                        <x-nav-link-child href="{{ route('productos.index') }}"
                            :active="request()->routeIs('productos.index')">
                            Productos
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('nutrientes.index') }}"
                            :active="request()->routeIs('nutrientes.index')">
                            Nutrientes
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('tabla_concentracion.index') }}"
                            :active="request()->routeIs('tabla_concentracion.index')">
                            Tabla de concentración
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorKardex" :active="request()->routeIs([
        'kardex.lista',
        'almacen.salida_productos',
        'almacen.salida_combustible',
    ])" logo="fa fa-clipboard-list"
                        text="Kardex y Almacén">
                        <x-nav-link-child href="{{ route('almacen.salida_productos') }}"
                            :active="request()->routeIs('almacen.salida_productos')">
                            Salida de Almacén Pesticidas y Fertilizantes
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('almacen.salida_combustible') }}"
                            :active="request()->routeIs('almacen.salida_combustible')">
                            Salida de Combustible
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('kardex.lista') }}"
                            :active="request()->routeIs('kardex.lista')">
                            Ver Kardex
                        </x-nav-link-child>
                    </x-nav-link-parent>


                    <x-nav-link-parent name="sectorConsolidados" :active="request()->routeIs('consolidado.riego')"
                        logo="fa fa-database" text="Consolidado">
                        <x-nav-link-child href="{{ route('consolidado.riego') }}"
                            :active="request()->routeIs('consolidado.riego')">
                            Riego
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorGastos" :active="request()->routeIs([
        'gastos.general',
        'contabilidad.costos_mensuales',
        'contabilidad.costos_generales',
    ])" logo="fa fa-calculator"
                        text="Contabilidad">
                        <x-nav-link-child href="{{ route('gastos.general') }}"
                            :active="request()->routeIs('gastos.general')">
                            Gasto General
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('contabilidad.costos_mensuales') }}"
                            :active="request()->routeIs('contabilidad.costos_mensuales')">
                            Costos Mensuales
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('contabilidad.costos_generales') }}"
                            :active="request()->routeIs('contabilidad.costos_generales')">
                            Costos Generales
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorProveedores" :active="request()->routeIs(['proveedores.index', 'maquinarias.index'])" logo="fa fa-users" text="Información General">
                        <x-nav-link-child href="{{ route('proveedores.index') }}"
                            :active="request()->routeIs('proveedores.index')">
                            Proveedores
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('maquinarias.index') }}"
                            :active="request()->routeIs('maquinarias.index')">
                            Maquinarias
                        </x-nav-link-child>
                    </x-nav-link-parent>

                    <x-nav-link-parent name="sectorConfiguracion" :active="request()->routeIs([
        'configuracion',
        'descuentos_afp',
        'configuracion.labores',
        'configuracion.labores_riego',
        'configuracion.tipo_asistencia',
    ])" logo="fa fa-cogs" text="Configuración">
                        <x-nav-link-child href="{{ route('configuracion') }}"
                            :active="request()->routeIs('configuracion')">
                            Parámetros
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('descuentos_afp') }}"
                            :active="request()->routeIs('descuentos_afp')">
                            Descuentos de AFP
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('configuracion.labores') }}"
                            :active="request()->routeIs('configuracion.labores')">
                            Labores
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('configuracion.labores_riego') }}"
                            :active="request()->routeIs('configuracion.labores_riego')">
                            Labores en Riego
                        </x-nav-link-child>
                        <x-nav-link-child href="{{ route('configuracion.tipo_asistencia') }}"
                            :active="request()->routeIs('configuracion.tipo_asistencia')">
                            Tipo de Asistencia
                        </x-nav-link-child>
                    </x-nav-link-parent>
                    @endhasanyrole
                </ul>
            </div>
        </nav>

        <!-- MENÚ DE USUARIO AL FINAL -->
        <div class="border-t border-gray-700 p-4">
            <div class="relative">
                <button onclick="toggleUserMenu()"
                    class="buton-on-sidebar w-full flex items-center p-2 rounded-lg text-white hover:bg-gray-800 transition-colors">
                    <!-- Avatar siempre visible -->
                    <div
                        class="w-8 h-8 flex-shrink-0 bg-gray-600 rounded-lg flex items-center justify-center text-white font-semibold text-sm">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <!-- Info del usuario solo cuando está expandido -->
                    <div class="menu-text flex-1 flex items-center justify-between">
                        <div class="text-left">
                            <div class="font-semibold text-sm truncate">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-400 truncate">Empleado</div>
                        </div>
                        <!-- Chevron solo cuando está expandido -->
                        <i id="user-chevron" class="menu-text fa fa-chevron-up transition-transform flex-shrink-0"></i>
                    </div>

                </button>

                <!-- Dropdown Menu -->
                <div id="user-dropdown"
                    class="absolute bottom-full left-0 w-full mb-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible transition-all duration-200">
                    <div class="p-2">
                        <!-- Información del usuario -->
                        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ Auth::user()->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
                        </div>

                        <!-- Opciones -->
                        <a href="{{ route('profile.show') }}"
                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                            <i class="fa fa-cogs w-4"></i>
                            Configuración
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md">
                                <i class="fa fa-sign-out-alt w-4"></i>
                                Salir
                            </button>
                        </form>
                    </div>
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
        document.addEventListener('click', function (event) {
            const userMenu = event.target.closest('#user-dropdown') || event.target.closest('button[onclick="toggleUserMenu()"]');
            if (!userMenu) {
                const dropdown = document.getElementById('user-dropdown');
                const chevron = document.getElementById('user-chevron');
                dropdown.classList.add('opacity-0', 'invisible');
                dropdown.classList.remove('opacity-100', 'visible');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        });
    </script>
</aside>