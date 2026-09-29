@php
    $counts = [
        'empleados' => ['new' => count($data['EMPLEADOS']['new'] ?? []), 'upd' => count($data['EMPLEADOS']['update'] ?? []) + count($data['EMPLEADOS']['warning'] ?? [])],
        'contrataciones' => ['new' => count($data['CONTRATACIONES']['new'] ?? []), 'upd' => count($data['CONTRATACIONES']['update'] ?? [])],
        'sueldos' => ['new' => count($data['SUELDOS']['new'] ?? []), 'upd' => count($data['SUELDOS']['update'] ?? [])],
        'hijos' => ['new' => count($data['HIJOS']['new'] ?? []), 'upd' => count($data['HIJOS']['update'] ?? [])],
    ];

    $totalNew = array_sum(array_column($counts, 'new'));
    $totalUpdate = array_sum(array_column($counts, 'upd'));
@endphp

<div class="space-y-4">
    <x-card>
        <div>
            <x-h4>Resumen de Importación</x-h4>
            <x-label>Verifica los cambios finales antes de persistir en la base de datos.</x-label>
        </div>

        <div class="mt-4 space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-green-100 bg-green-50/50 p-4 flex items-start justify-between dark:bg-green-600 dark:border-green-600">
                    <div>
                        <p class="text-xs font-semibold text-green-600 uppercase tracking-wider dark:text-white">Registros Nuevos</p>
                        <p class="text-3xl font-black text-green-700 dark:text-gray-100">{{ $totalNew }}</p>
                    </div>
                    <span class="bg-green-600 text-white text-[10px] font-bold px-2 py-1 rounded dark:bg-white dark:text-green-700">NUEVO</span>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4 flex items-start justify-between dark:bg-blue-600 dark:border-blue-600">
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider dark:text-white">A Actualizar</p>
                        <p class="text-3xl font-black text-blue-700 dark:text-gray-100">{{ $totalUpdate }}</p>
                    </div>
                    <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded dark:bg-white dark:text-green-700">CAMBIOS</span>
                </div>
            </div>

            <div class="space-y-3">
                <x-h4>Detalles por Categoría</x-h4>
                <div class="grid gap-2">
                    @foreach(['EMPLEADOS' => ['green', 'users'], 'CONTRATACIONES' => ['blue', 'file-text'], 'SUELDOS' => ['yellow', 'dollar-sign'], 'HIJOS' => ['purple', 'smile']] as $key => $config)
                        @php $lowKey = strtolower($key); @endphp
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 p-3 dark:bg-gray-700 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-lg bg-{{ $config[0] }}-100 flex items-center justify-center dark:bg-{{ $config[0] }}-600">
                                    <span class="text-sm font-bold text-{{ $config[0] }}-700 dark:text-gray-200">
                                        {{ $counts[$lowKey]['new'] + $counts[$lowKey]['upd'] }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ ucfirst($lowKey) }}</p>
                                    <p class="text-xs text-gray-500 italic dark:text-gray-200">
                                        {{ $counts[$lowKey]['new'] }} nuevos + {{ $counts[$lowKey]['upd'] }} actualizaciones
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($totalUpdate > 0)
                <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-100 dark:bg-amber-600 dark:border-amber-500">
                    <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm text-amber-800">
                        <strong>Atención:</strong> Los datos existentes para los {{ $totalUpdate }} registros marcados serán actualizados con la información del Excel.
                    </p>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <x-button 
                    wire:click="cancelar" 
                    class="w-full" variant="secondary"
                >
                    Cancelar
                </x-button>
                <x-button 
                    wire:click="procesarImportacion" 
                    wire:loading.attr="disabled"
                    class="w-full" variant="success"
                >
                    <span wire:loading.remove wire:target="procesarImportacion">Aprobar Importación</span>
                    <span wire:loading wire:target="procesarImportacion">Procesando...</span>
                </x-button>
            </div>
        </div>
    </x-card>
</div>