<x-card>
    <x-flex class="justify-between">
        <div>
            <x-title>
                Asignación de Kardex
            </x-title>
            <x-label>
                Producto:
                <span class="text-blue-300 font-semibold">
                    {{ $productoNombre }}
                </span>
            </x-label>
        </div>

        <div class="flex gap-2">
            <div class="ms-3 relative">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                OPCIONES
                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            @if ($kardexBlanco)
                                <x-dropdown-link
                                    href="{{ route('gestion_insumos.kardex.detalle', $kardexBlanco->id) }}">
                                    Ver Kardex Blanco
                                </x-dropdown-link>
                            @endif
                            @if ($kardexNegro)
                                <x-dropdown-link href="{{ route('gestion_insumos.kardex.detalle', $kardexNegro->id) }}">
                                    Ver Kardex Negro
                                </x-dropdown-link>
                            @endif
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </x-flex>
</x-card>
