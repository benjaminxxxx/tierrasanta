<x-flex class="justify-center">
    <div class="relative">
        <x-dropdown align="right">
            <x-slot name="trigger">
                <span class="inline-flex rounded-md w-full lg:w-auto">
                    <x-button type="button" class="flex items-center justify-center">
                        <i class="fa fa-cogs mr-2"></i> Opciones

                        <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                        </svg>
                    </x-button>
                </span>
            </x-slot>

            <x-slot name="content">
                <div class="w-full">
                    <x-dropdown-link class="text-center" @click="agregarCuadrillerosEnTramo">
                        Agregar cuadrilleros
                    </x-dropdown-link>
                    <x-dropdown-link class="text-center" @click="$wire.dispatch('asignarCostosPorFecha',{tramoId: {{ $tramoLaboral->id}}})">
                        Asignar costos por jornal
                    </x-dropdown-link>
                    <x-dropdown-link class="text-center" @click="$wire.dispatch('abrirGastosAdicionales')">
                        Agregar/Quitar gastos adicionales
                    </x-dropdown-link>
                    <x-dropdown-link class="text-center" @click="$wire.dispatch('editarTramo')">
                        Editar tramo
                    </x-dropdown-link>
                    <x-dropdown-link class="text-center" @click="$wire.dispatch('eliminarTramo')">
                        Eliminar tramo
                    </x-dropdown-link>
                </div>
            </x-slot>
        </x-dropdown>
    </div>
</x-flex>