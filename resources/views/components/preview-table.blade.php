@props(['records', 'type', 'showChanges' => false])

@php
    $columnHeaders = [
        'empleados' => [
            'apellido_paterno' => 'Apellido Paterno',
            'apellido_materno' => 'Apellido Materno',
            'nombres' => 'Nombres',
            'documento' => 'DNI',
            'email' => 'Email',
            'fecha_ingreso' => 'Fecha Ingreso',
        ],
        'contrataciones' => [
            'documento' => 'DNI',
            'tipo_contrato' => 'Tipo Contrato',
            'fecha_inicio' => 'Fecha Inicio',
            'cargo_codigo' => 'Cargo',
            'tipo_planilla' => 'Planilla',
        ],
        'sueldos' => [
            'documento' => 'DNI',
            'sueldo' => 'Sueldo',
            'fecha_inicio' => 'Desde',
            'fecha_fin' => 'Hasta',
        ],
        'hijos' => [
            'documento_padre' => 'DNI Padre',
            'nombres' => 'Nombre Hijo',
            'documento' => 'DNI Hijo',
            'esta_estudiando' => 'Estudiando',
        ],
    ];

    $headers = $columnHeaders[$type] ?? [];
    // Tomamos las primeras 3 llaves para la vista resumida
    $summaryKeys = array_slice(array_keys($headers), 0, 3);
@endphp

<div x-data="{ expandedRows: [] }"
     class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm
            dark:bg-gray-800 dark:border-gray-700">

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">

            <!-- HEADER -->
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold
                          dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                <tr>
                    @if($showChanges)
                        <th class="w-10 px-4 py-3 text-center">
                            <span class="sr-only">Estado</span>
                        </th>
                    @endif

                    @foreach($summaryKeys as $key)
                        <th class="px-4 py-3">{{ $headers[$key] }}</th>
                    @endforeach

                    <th class="px-4 py-3 text-center">Detalles</th>
                </tr>
            </thead>

            <!-- BODY -->
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-600">
                @forelse($records as $index => $record)
                    @php
                        $hasChanges = isset($record['changes']) && count($record['changes']) > 0;
                    @endphp

                    <!-- ROW -->
                    <tr class="transition-colors
                               hover:bg-gray-50 dark:hover:bg-gray-700/50">

                        @if($showChanges)
                            <td class="px-4 py-3 text-center">
                                <div class="h-2.5 w-2.5 rounded-full mx-auto
                                    {{ $hasChanges
                                        ? 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]'
                                        : 'bg-green-500 shadow-[0_0_6px_rgba(34,197,94,0.6)]'
                                    }}">
                                </div>
                            </td>
                        @endif

                        @foreach($summaryKeys as $key)
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="
                                        {{ isset($record['changes'][$key])
                                            ? 'font-bold text-blue-700 dark:text-blue-400'
                                            : 'text-gray-900 dark:text-gray-100'
                                        }}">
                                        {{ $record[$key] ?? '---' }}
                                    </span>

                                    @if(isset($record['changes'][$key]))
                                        <span class="text-[10px] italic leading-tight
                                                     text-orange-500 dark:text-orange-400">
                                            Cambió
                                        </span>
                                    @endif
                                </div>
                            </td>
                        @endforeach

                        <!-- TOGGLE -->
                        <td class="px-4 py-3 text-center">
                            <button
                                @click="expandedRows.includes({{ $index }})
                                    ? expandedRows = expandedRows.filter(i => i !== {{ $index }})
                                    : expandedRows.push({{ $index }})"
                                class="p-1 rounded-full transition-colors
                                       hover:bg-gray-100 dark:hover:bg-gray-600">
                                <svg x-show="!expandedRows.includes({{ $index }})"
                                     class="w-5 h-5 text-gray-400 dark:text-gray-300"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 9l-7 7-7-7" />
                                </svg>

                                <svg x-show="expandedRows.includes({{ $index }})"
                                     class="w-5 h-5 text-gray-400 dark:text-gray-300"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 15l7-7 7 7" />
                                </svg>
                            </button>
                        </td>
                    </tr>

                    <!-- EXPANDED ROW -->
                    <tr x-show="expandedRows.includes({{ $index }})" x-cloak
                        class="bg-gray-50/50 dark:bg-gray-900/40">

                        <td colspan="{{ count($summaryKeys) + ($showChanges ? 2 : 1) }}"
                            class="px-6 py-4">

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4
                                        bg-white border border-gray-100 rounded-md shadow-inner
                                        dark:bg-gray-800 dark:border-gray-700">

                                @foreach($headers as $key => $label)
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider
                                                  text-gray-400 dark:text-gray-500">
                                            {{ $label }}
                                        </p>

                                        <p class="text-sm
                                            {{ isset($record['changes'][$key])
                                                ? 'text-blue-600 dark:text-blue-400 font-medium'
                                                : 'text-gray-700 dark:text-gray-200'
                                            }}">
                                            {{ $record[$key] ?? '---' }}
                                        </p>

                                        @if(isset($record['changes'][$key]))
                                            <p class="text-[10px] mt-1 line-through
                                                     text-gray-400 dark:text-gray-500">
                                                Anterior: {{ $record['changes'][$key] }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="100%"
                            class="px-4 py-8 text-center italic
                                   text-gray-500 dark:text-gray-400">
                            No hay registros para mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
