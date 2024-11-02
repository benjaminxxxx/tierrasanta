<aside :class="sidebarToggle ? 'translate-x-0' : '-translate-x-full'"
    class="absolute left-0 top-0 z-9999 flex h-screen w-72.5 flex-col overflow-y-hidden bg-black duration-300 ease-linear dark:bg-boxdark lg:static lg:translate-x-0"
    @click.outside="sidebarToggle = false">
    <!-- SIDEBAR HEADER -->
    <div class="flex items-center justify-between gap-2 px-6 py-5.5 lg:py-6.5">
        <a href="{{ route('inicio') }}">
            <img src="{{ asset('images/logo/logo-horizontal.svg') }}" class="w-full" alt="Logo" />
        </a>

        <button class="block lg:hidden" @click.stop="sidebarToggle = !sidebarToggle">
            <svg class="fill-current" width="20" height="18" viewBox="0 0 20 18" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M19 8.175H2.98748L9.36248 1.6875C9.69998 1.35 9.69998 0.825 9.36248 0.4875C9.02498 0.15 8.49998 0.15 8.16248 0.4875L0.399976 8.3625C0.0624756 8.7 0.0624756 9.225 0.399976 9.5625L8.16248 17.4375C8.31248 17.5875 8.53748 17.7 8.76248 17.7C8.98748 17.7 9.17498 17.625 9.36248 17.475C9.69998 17.1375 9.69998 16.6125 9.36248 16.275L3.02498 9.8625H19C19.45 9.8625 19.825 9.4875 19.825 9.0375C19.825 8.55 19.45 8.175 19 8.175Z"
                    fill="" />
            </svg>
        </button>
    </div>
    <!-- SIDEBAR HEADER -->

    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <!-- Sidebar Menu -->
        <nav class="mt-5 px-4 py-4 lg:mt-9 lg:px-6" x-data="{ selected: $persist('Dashboard') }">
            <!-- Menu Group -->
            <div>
                <h3 class="mb-4 ml-4 text-sm font-medium text-bodydark2">MENU</h3>

                <ul class="mb-6 flex flex-col gap-1.5">
                    <x-nav-link-parent name="sectorPlanilla" href="#" :active="request()->routeIs(['planilla.asistencia'])">
                        
                        <div class="w-6 text-center">
                            <i class="fa fa-table"></i>
                        </div>
                        Planilla
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('planilla.asistencia') }}" :active="request()->routeIs('planilla.asistencia')">
                                Asistencia
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('planilla.blanco') }}" :active="request()->routeIs('planilla.blanco')">
                                Blanco
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorEmpleado" href="{{ route('empleados') }}" :active="request()->routeIs(['empleados','empleados.asignacion_familiar'])">
                        
                        <div class="w-6 text-center">
                            <i class="fa fa-users"></i>
                        </div>
                        Empleado
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('empleados') }}" :active="request()->routeIs('empleados')">
                                Lista de Empleados
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('empleados.asignacion_familiar') }}" :active="request()->routeIs('empleados.asignacion_familiar')">
                                Asignación Familiar
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorSistema" href="{{ route('usuarios') }}" :active="request()->routeIs(['usuarios'])">
                        
                        <div class="w-6 text-center">
                            <i class="fas fa-palette"></i>
                        </div>
                        Sistema
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('usuarios') }}" :active="request()->routeIs('usuarios')">
                                Usuarios
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorCuadrilla" href="#" :active="request()->routeIs(['cuadrilla.grupos','cuadrilla.cuadrilleros'])">
                        
                        <div class="w-6 text-center">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                        Cuadrilla
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('cuadrilla.cuadrilleros') }}" :active="request()->routeIs('cuadrilla.cuadrilleros')">
                                Cuadrilleros
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('cuadrilla.grupos') }}" :active="request()->routeIs('cuadrilla.grupos')">
                                Grupos
                            </x-nav-link-child>
                            
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorCampo" href="#" :active="request()->routeIs(['campo.mapa','campo.riego','campo.campania'])">
                        
                        <div class="w-6 text-center">
                            <i class="fa fa-leaf"></i>
                        </div>
                        Campo
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('campo.mapa') }}" :active="request()->routeIs('campo.mapa')">
                                Mapa
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('campo.riego') }}" :active="request()->routeIs('campo.riego')">
                                Riego
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('campo.campania') }}" :active="request()->routeIs('campo.campania')">
                                Campañas
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorReportes" href="#" :active="request()->routeIs(['reporte.reporte_diario','reporte.reporte_diario_riego','cuadrilla.asistencia','almacen.salida_productos'])">
                      
                        <div class="w-6 text-center">
                            <i class="fa fa-database"></i>
                        </div>
                        Reporte Diario
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('reporte.reporte_diario') }}" :active="request()->routeIs('reporte.reporte_diario')">
                                Planilla
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('reporte.reporte_diario_riego') }}" :active="request()->routeIs('reporte.reporte_diario_riego')">
                                Regadores
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('cuadrilla.asistencia') }}" :active="request()->routeIs('cuadrilla.asistencia')">
                                Cuadrilleros
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('almacen.salida_productos') }}" :active="request()->routeIs('almacen.salida_productos')">
                                Salida de Almacén Pesticidas y Fertilizantes
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorConsolidados" :active="request()->routeIs('consolidado.riego')">
                        
                        <div class="w-6 text-center">
                            <i class="fa fa-database"></i>
                        </div>
                        Consolidado
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('consolidado.riego') }}" :active="request()->routeIs('consolidado.riego')">
                                Riego
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorProveedores" :active="request()->routeIs(['proveedores.index','productos.index'])">
                        
                        <div class="w-6 text-center">
                            <i class="fa fa-users"></i>
                        </div>
                        Información General
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('proveedores.index') }}" :active="request()->routeIs('proveedores.index')">
                                Proveedores
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('productos.index') }}" :active="request()->routeIs('productos.index')">
                                Productos
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                    <x-nav-link-parent name="sectorConfiguracion" :active="request()->routeIs(['configuracion','descuentos_afp','configuracion.labores_riego','configuracion.tipo_asistencia'])">
                        <div class="w-6 text-center">
                            <i class="fa fa-cogs"></i>
                        </div>
                        Configuración
                        <x-slot name="children">
                            <x-nav-link-child href="{{ route('configuracion') }}" :active="request()->routeIs('configuracion')">
                           
                                Parámetros
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('descuentos_afp') }}" :active="request()->routeIs('descuentos_afp')">
                             
                                Descuentos de AFP
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('configuracion.labores_riego') }}" :active="request()->routeIs('configuracion.labores_riego')">
                                Labores en Riego
                            </x-nav-link-child>
                            <x-nav-link-child href="{{ route('configuracion.tipo_asistencia') }}" :active="request()->routeIs('configuracion.tipo_asistencia')">
                                Tipo de Asistencia
                            </x-nav-link-child>
                        </x-slot>
                    </x-nav-link-parent>
                </ul>
            </div>
        </nav>
        <!-- Sidebar Menu -->
    </div>
</aside>
