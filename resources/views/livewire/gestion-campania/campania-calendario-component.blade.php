<div x-data="{
    buscar: '',
    soloConCampanias: true,
    soloActivas: false,
}" class="space-y-8 p-4 bg-white dark:bg-gray-900 min-h-screen">

    <div class="flex gap-4 mb-6">
        <x-input type="text" x-model="buscar" placeholder="Buscar por Campo..." />

        <x-label class="flex items-center gap-2 text-sm">
            <input type="checkbox" x-model="soloConCampanias">
            Solo campos con campañas
        </x-label>

        <x-label class="flex items-center gap-2 text-sm">
            <input type="checkbox" x-model="soloActivas">
            Solo campañas en curso
        </x-label>
    </div>

    @foreach ($timeline['campos'] as $campo)
        <template
            x-if="
        // 1. Solo campos con campañas
        (!soloConCampanias || {{ count($campo['campanias']) }} > 0)
&&

        // 2. Solo campos con campañas activas
        (
            !soloActivas ||
            {{ collect($campo['campanias'])->contains('activa', true) ? 'true' : 'false' }}
        )

        &&

        // 3. Buscar por nombre del campo
        (
            buscar === '' ||
            '{{ strtolower($campo['nombre']) }}'.includes(buscar.toLowerCase())
        )
    ">

            <div class="flex flex-col space-y-4">
                <!-- contenido del campo -->
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 px-2 tracking-tight">
                    {{ $campo['nombre'] }}
                </h2>

                <div class="flex overflow-x-auto pb-4 gap-4">
                    @forelse ($campo['campanias'] as $campania)
                        @php
                            $esActiva = is_null($campania['fin']);
                        @endphp

                        <div class="flex-none w-[200px] group">
                            <div
                                class="relative h-full flex flex-col bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-all border-l-4 {{ $esActiva ? 'border-l-green-500' : 'border-l-blue-500' }}">

                                <div class="mb-3">
                                    <span
                                        class="text-[10px] uppercase tracking-widest font-semibold {{ $esActiva ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">
                                        {{ $esActiva ? 'En curso' : 'Finalizada' }}
                                    </span>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight truncate"
                                        title="{{ $campania['nombre'] }}">
                                        {{ $campania['nombre'] }}
                                    </h3>
                                </div>

                                <div class="flex-grow space-y-2 mb-4">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <p class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            {{ $campania['inicio'] }}
                                        </p>
                                        <p class="flex items-center mt-1">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                            </svg>
                                            {{ $campania['fin'] ? $campania['fin'] : 'Actualidad' }}
                                        </p>
                                    </div>

                                    <div
                                        class="inline-flex items-center px-2 py-1 rounded-md bg-white dark:bg-gray-700 text-[11px] font-medium text-gray-600 dark:text-gray-300 border border-gray-100 dark:border-gray-600">
                                        {{ $campania['duracion_humana'] }}
                                    </div>
                                </div>

                                <x-button href="{{ route('campania.x.campo',['campania'=>$campania['id']]) }}" target="_blank">
                                    Ver campaña
                                </x-button>
                            </div>
                        </div>
                    @empty
                        <div
                            class="flex items-center justify-center h-32 w-full border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <span class="text-gray-400 dark:text-gray-500 text-sm italic">Sin campañas
                                registradas</span>
                        </div>
                    @endforelse
                </div>
            </div>

            @if (!$loop->last)
                <hr class="border-gray-100 dark:border-gray-800 my-2">
            @endif
        </template>
    @endforeach

</div>
