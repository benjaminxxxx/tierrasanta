<div>
    <x-dropdown align="right">
        <x-slot name="trigger">
            <span class="inline-flex rounded-md w-full lg:w-auto">
                <x-button type="button" class="flex items-center justify-center">
                    Opciones adicionales

                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                    </svg>
                </x-button>
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="w-full">
                <x-dropdown-link class="text-center" href="{{ route('planilla.importar') }}">
                    <i class="fa fa-file-excel"></i> Importar Empleados
                </x-dropdown-link>
                <x-dropdown-link class="text-center" wire:click="ordenarPlanillaAgraria">
                    <i class="fa fa-list"></i> Ordenar Planilla Agraria
                </x-dropdown-link>
                <x-dropdown-link class="text-center" wire:click="abrirFormCambioMasivoSueldo">
                    <i class="fa fa-money-bill"></i> Cambio de Sueldo Masivo
                </x-dropdown-link>
            </div>
        </x-slot>
    </x-dropdown>
</div>