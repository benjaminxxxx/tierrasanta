@props(['ingreso'])
<x-dropdown align="right" width="60">
    <x-slot name="trigger">
        <span class="inline-flex rounded-md">
            <button type="button"
                class="inline-flex items-center px-3 py-2 border border-transparent leading-4 font-medium rounded-md dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                Acciones
                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
            </button>
        </span>
    </x-slot>
    <x-slot name="content">
        <div class="w-60">
            <x-dropdown-link class="text-center" @click="$wire.dispatch('editarIngreso',{lote:{{ $ingreso->lote }}})">
                <i class="fa fa-list"></i> Sublotes
            </x-dropdown-link>
            <x-dropdown-link class="text-center"
                @click="$wire.dispatch('agregarVenteado',{ingresoId:{{ $ingreso->id }}})">
                <i class="fas fa-wind"></i> Venteado
            </x-dropdown-link>
            <x-dropdown-link class="text-center"
                @click="$wire.dispatch('agregarFiltrado',{ingresoId:{{ $ingreso->id }}})">
                <i class="fa-solid fa-filter"></i> Filtrado
            </x-dropdown-link>
            <x-dropdown-link class="text-center" @click="$wire.dispatch('abrirMapa',{ingresoId:{{ $ingreso->id }}})">
                <i class="fa fa-table"></i> Detalle gráfico
            </x-dropdown-link>

            <x-dropdown-link class="text-center" wire:click="eliminarIngreso({{ $ingreso->id }})">
                <i class="fa fa-remove"></i> Eliminar ingreso
            </x-dropdown-link>

        </div>
    </x-slot>
</x-dropdown>