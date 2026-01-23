@if ($campaniaSeleccionada)
    <x-flex class="justify-center">
        @if($campania && $campania->gasto_resumen_bdd_file)
        <x-button href="{{ Storage::disk('public')->url($campania->gasto_resumen_bdd_file) }}">
            <i class="fa fa-file-excel"></i> Descargar Reporte BDD
        </x-button>
        @endif
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
                        <x-dropdown-link class="text-center"
                            @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campaniaSeleccionada }}})">
                            Editar Campaña
                        </x-dropdown-link>
                        <x-dropdown-link class="text-center" wire:click="generarBdd({{ $campaniaSeleccionada }})">
                            Generar Reporte BDD
                        </x-dropdown-link>
                        <x-dropdown-link class="text-center !text-red-600"
                            wire:confirm="¿Estás seguro de eliminar esta campaña?"
                            wire:click="eliminarCampania({{ $campaniaSeleccionada }})">
                            Eliminar Campaña
                        </x-dropdown-link>
                    </div>
                </x-slot>
            </x-dropdown>
        </div>
    </x-flex>
@endif
