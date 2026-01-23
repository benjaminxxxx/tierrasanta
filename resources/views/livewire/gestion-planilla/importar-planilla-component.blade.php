<div x-data="importacionPlanilla">
    <div class="mx-auto max-w-6xl space-y-6">

        <div class="space-y-2">
            <x-title>
                Importador de Datos de Empleados
            </x-title>
            <x-subtitle>
                Carga datos de empleados, contrataciones, sueldos e hijos desde Excel
            </x-subtitle>
        </div>

        <x-card class="flex">

            <x-button @click="activeTab = 'upload'"
                x-bind:class="activeTab === 'upload'
                    ?
                    '' :
                    'text-gray-900 bg-white dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700'"
                class="flex-1 flex">

                <i class="fa-solid fa-upload mr-2 text-sm"></i>
                Cargar
            </x-button>

            <x-button @click="activeTab = 'preview'" x-bind:disabled="!archivoCargado"
                x-bind:class="activeTab === 'preview'
                    ?
                    '' :
                    '!text-gray-900 !bg-white dark:!bg-gray-800 dark:!text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700'"
                class="flex-1 flex">

                <i class="fa-solid fa-circle-info mr-2 text-sm"></i>
                Vista Previa
            </x-button>


            <x-button @click="activeTab = 'complete'" x-bind:disabled="!archivoCargado"
                x-bind:class="activeTab === 'complete'
                    ?
                    '' :
                    '!text-gray-900 !bg-white dark:!bg-gray-800 dark:!text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700'"
                class="flex-1 flex">

                <i class="fa-solid fa-circle-check mr-2 text-sm"></i>
                Confirmación
            </x-button>


        </x-card>

        <div>
            <div x-show="activeTab === 'upload'" x-transition:enter.duration.300ms class="space-y-4">

                <x-card>
                    <x-h4>
                        Cargar Archivo Excel
                    </x-h4>
                    <x-label>
                        Sube tu archivo Excel con los datos de empleados
                    </x-label>

                    <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="
            isDragging = false;
            $refs.fileInput.files = $event.dataTransfer.files;
            $refs.fileInput.dispatchEvent(new Event('change'))
        "
                        class="flex items-center justify-center w-full">

                        <label
                            class="flex flex-col items-center justify-center w-full py-4
                   border-2 border-dashed rounded-lg cursor-pointer
                   transition-colors
                   "
                            :class="[
                                isDragging ?
                                'border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                                'border-gray-300 bg-gray-50 dark:bg-gray-700',
                                'dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'
                            ]">

                            <!-- Archivo cargado -->
                            <template x-if="archivoCargado">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center">
                                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full mb-3">
                                        <i
                                            class="fa-solid fa-circle-check text-green-600 dark:text-green-400 text-xl"></i>
                                    </div>

                                    <p class="font-medium text-gray-900 dark:text-white" x-text="nombreArchivo"></p>

                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Archivo listo para procesar
                                    </p>
                                </div>
                            </template>

                            <!-- Sin archivo -->
                            <template x-if="!archivoCargado">
                                <div class="flex flex-col items-center justify-center pb-6 text-center">
                                    <i
                                        class="fa-solid fa-cloud-arrow-up
                              text-3xl mb-4
                              text-gray-500 dark:text-gray-400"></i>

                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">
                                            Haz clic para subir
                                        </span>
                                        o arrastra y suelta
                                    </p>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Formatos soportados: .xlsx, .xls
                                    </p>
                                </div>
                            </template>

                            <input x-ref="fileInput" type="file" wire:model="archivo" accept=".xlsx,.xls"
                                class="hidden" />
                        </label>
                    </div>

                    <!-- Loading Livewire -->
                    <div wire:loading wire:target="archivo" class="mt-4 text-center">
                        <span class="text-xs text-blue-600 dark:text-blue-400 animate-pulse">
                            Subiendo y validando archivo...
                        </span>
                    </div>
                </x-card>

                <x-card class="mt-4">
                    <x-h4>Descargar Plantilla</x-h4>
                    <x-label>Descarga la plantilla para completarla con tus datos</x-label>

                    <x-button wire:click="descargarPlanilla">
                        <i class="fa fa-download"></i> Descargar Planilla Actualizada
                    </x-button>
                </x-card>
            </div>

            <div x-show="activeTab === 'preview'" x-transition:enter.duration.300ms>
                @if (count($data) > 0)
                    <x-card>
                        <x-h4>
                            Vista Previa de Datos
                        </x-h4>
                        <x-label>
                            Revisa los cambios antes de procesar la importación.
                        </x-label>

                        <div x-data="{ subTab: 'empleados' }">
                            <div class="flex border-b border-gray-200 mb-4">
                                @foreach (['EMPLEADOS', 'CONTRATACIONES', 'SUELDOS', 'HIJOS'] as $tab)
                                    <button @click="subTab = '{{ strtolower($tab) }}'"
                                        :class="subTab === '{{ strtolower($tab) }}' ? 'border-blue-500 text-blue-600' :
                                            'border-transparent text-gray-500 hover:text-gray-700'"
                                        class="py-2 px-4 border-b-2 font-medium text-sm flex items-center">
                                        {{ ucfirst(strtolower($tab)) }}
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-gray-100 rounded-full">
                                            {{ count($data[$tab]['new'] ?? []) + count($data[$tab]['update'] ?? []) + count($data[$tab]['warning'] ?? []) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div x-show="subTab === 'empleados'" class="space-y-6">
                                @if (count($data['EMPLEADOS']['warning'] ?? []) > 0)
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold">ALERTA:
                                                POSIBLE CAMBIO DNI</span>
                                        </div>
                                        <div class="overflow-x-auto border rounded-lg">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left">Nombres</th>
                                                        <th class="px-4 py-2 text-left">DNI Anterior</th>
                                                        <th class="px-4 py-2 text-left">DNI Nuevo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($data['EMPLEADOS']['warning'] as $emp)
                                                        <tr class="hover:bg-yellow-50">
                                                            <td class="px-4 py-2">{{ $emp['nombres'] }}
                                                                {{ $emp['apellido_paterno'] }}</td>
                                                            <td class="px-4 py-2 text-red-600 line-through">
                                                                {{ $emp['documento_anterior'] }}</td>
                                                            <td class="px-4 py-2 text-green-600 font-bold">
                                                                {{ $emp['documento'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                                {{-- NUEVOS --}}
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white">
                                            NUEVO
                                        </span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ count($data['EMPLEADOS']['new']) }} registro(s)
                                        </span>
                                    </div>

                                    <x-preview-table :records="$data['EMPLEADOS']['new']" type="empleados" showChanges="true" />
                                </div>

                                {{-- ACTUALIZAR --}}
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                            ACTUALIZAR
                                        </span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ count($data['EMPLEADOS']['update']) }} registro(s)
                                        </span>
                                    </div>

                                    <x-preview-table :records="$data['EMPLEADOS']['update']" type="empleados" showChanges="true" />
                                </div>

                                {{-- ELIMINADOS --}}
                                <div class="space-y-2 mt-8">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded bg-rose-600 px-2 py-1 text-xs font-semibold text-white uppercase">
                                            Eliminar / No encontrados
                                        </span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ count($data['EMPLEADOS']['deleted']) }} registro(s) se
                                            eliminarán del sistema
                                        </span>
                                    </div>

                                    <div class="opacity-75 grayscale-[0.5]">
                                        <x-preview-table :records="$data['EMPLEADOS']['deleted']" type="empleados" variant="deleted" />
                                    </div>

                                    <p class="text-[10px] text-rose-500 italic">
                                        * Estos registros existen en la base de datos pero no están en el Excel
                                        cargado.
                                    </p>
                                </div>
                                <div x-show="subTab === 'contrataciones'" class="space-y-6">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white">
                                                NUEVO
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['CONTRATACIONES']['new']) }} registro(s)
                                            </span>
                                        </div>
                                        <x-preview-table :records="$data['CONTRATACIONES']['new']" type="contrataciones" showChanges="true" />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                                ACTUALIZAR
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['CONTRATACIONES']['update']) }} registro(s)
                                            </span>
                                        </div>
                                        <x-preview-table :records="$data['CONTRATACIONES']['update']" type="contrataciones" showChanges="true" />
                                    </div>
                                </div>
                                <div x-show="subTab === 'sueldos'" class="space-y-6">

                                    {{-- NUEVOS --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white">
                                                NUEVO
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['SUELDOS']['new']) }} registro(s)
                                            </span>
                                        </div>

                                        <x-preview-table :records="$data['SUELDOS']['new']" type="sueldos" showChanges="true" />
                                    </div>

                                    {{-- ACTUALIZAR --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                                ACTUALIZAR
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['SUELDOS']['update']) }} registro(s)
                                            </span>
                                        </div>

                                        <x-preview-table :records="$data['SUELDOS']['update']" type="sueldos" showChanges="true" />
                                    </div>
                                </div>
                                <div x-show="subTab === 'hijos'" class="space-y-6">
                                    {{-- NUEVOS --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white">
                                                NUEVO
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['HIJOS']['new']) }} registro(s)
                                            </span>
                                        </div>

                                        <x-preview-table :records="$data['HIJOS']['new']" type="hijos" showChanges="true" />
                                    </div>

                                    {{-- ACTUALIZAR --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                                ACTUALIZAR
                                            </span>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                                {{ count($data['HIJOS']['update']) }} registro(s)
                                            </span>
                                        </div>

                                        <x-preview-table :records="$data['HIJOS']['update']" type="hijos" showChanges="true" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between mt-6">
                                <x-button type="button" variant="secondary" @click="activeTab = 'upload'">
                                    Volver
                                </x-button>

                                <x-button type="button" @click="activeTab = 'complete'">
                                    Siguiente
                                </x-button>
                            </div>
                    </x-card>
                @endif
            </div>

            <div x-show="activeTab === 'complete'" x-transition:enter.duration.300ms>
                @include('livewire.gestion-planilla.partials.import-summary')
            </div>
        </div>
    </div>
</div>

@script
    <script>
        Alpine.data('importacionPlanilla', () => ({
            activeTab: @entangle('activeTab'),
            isDragging: false,
            archivoCargado: @entangle('archivoCargado'),
            nombreArchivo: @entangle('nombreArchivo'),
        }));
    </script>
@endscript
