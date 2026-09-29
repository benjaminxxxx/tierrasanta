<div>
    <div class="md:flex gap-5">
        <div class="flex-1 space-y-4">
            <x-title>Comisiones y Primas de Seguro AFP</x-title>

            <x-card>
                {{-- Selector de periodo, siempre visible --}}
                <div>
                    <x-label for="fecha_inicio">Mes de devengue:</x-label>
                    <x-select wire:model.live="fecha_inicio" class="!w-auto mt-1">
                        @foreach ($fechas as $fecha)
                            <option value="{{ $fecha }}">{{ $fecha }}</option>
                        @endforeach
                    </x-select>
                </div>

                {{-- MODO LECTURA: solo se ve cuando NO se está registrando --}}
                @if (!$modoRegistro)
                    <div class="mt-6">
                        @if ($registros->isEmpty())
                            <x-danger
                                class="!bg-transparent border border-dashed border-zinc-300 dark:border-zinc-700 text-center py-6">
                                <p class="font-medium">No hay valores registrados para {{ $fecha_inicio }}.</p>
                                <p class="text-sm mt-1 text-zinc-500">Registra los montos de la SBS para este mes.</p>
                            </x-danger>
                        @else
                            <x-table>
                                <x-slot name="thead">
                                    <x-tr :level="2">
                                        <x-th value="AFP" />
                                        <x-th value="Comisión sobre flujo" class="text-center" />
                                        <x-th value="Comisión anual sobre saldo" class="text-center" />
                                        <x-th value="Prima de seguros" class="text-center" />
                                        <x-th value="Aporte obligatorio" class="text-center" />
                                        <x-th value="Rem. máxima asegurable" class="text-center" />
                                    </x-tr>
                                </x-slot>
                                <x-slot name="tbody">
                                    @foreach ($registros as $r)
                                        <x-tr>
                                            <x-td class="font-semibold">{{ $r->referencia }}</x-td>
                                            <x-td class="text-center">{{ number_format($r->comision_flujo, 2) }}%</x-td>
                                            <x-td class="text-center">{{ number_format($r->comision_saldo, 2) }}%</x-td>
                                            <x-td class="text-center">{{ number_format($r->prima_seguros, 2) }}%</x-td>
                                            <x-td class="text-center">{{ number_format($r->aporte_obligatorio, 2) }}%</x-td>
                                            <x-td class="text-center">
                                                {{ $r->remuneracion_maxima_asegurable ? number_format($r->remuneracion_maxima_asegurable, 2) : '—' }}
                                            </x-td>
                                        </x-tr>
                                    @endforeach
                                </x-slot>
                            </x-table>
                        @endif

                        <x-flex class="justify-end mt-4 gap-2">
                            <x-button wire:click="abrirModoRegistro">
                                <i class="fa fa-pen"></i>
                                {{ $registros->isEmpty() ? 'Registrar valores' : 'Reemplazar valores de este mes' }}
                            </x-button>

                            @if ($registros->isNotEmpty())
                                <x-button variant="danger" wire:click="eliminarRegistros"
                                    wire:confirm="¿Eliminar los valores registrados para {{ $fecha_inicio }}?">
                                    <i class="fa fa-remove"></i> Eliminar
                                </x-button>
                            @endif
                        </x-flex>
                    </div>
                @endif

                {{-- MODO REGISTRO: solo se ve al presionar el botón de arriba --}}
                @if ($modoRegistro)
                    <div class="mt-6 border-t border-zinc-200 dark:border-zinc-800 pt-4">
                        <x-label class="block mb-2">
                            1. Abre el
                            <a class="text-blue-500 font-medium underline" target="_blank"
                                href="https://www.sbs.gob.pe/app/spp/empleadores/comisiones_spp/Paginas/comision_prima.aspx">
                                enlace de la SBS
                            </a>
                            y copia la tabla completa (encabezados incluidos, no hace falta quitarlos).
                        </x-label>
                        <x-label class="block mb-4">
                            2. Pega el contenido tal cual aquí abajo y presiona "Generar Montos".
                        </x-label>

                        <x-textarea rows="6" placeholder="Pega aquí la tabla copiada de la SBS..."
                            wire:model="informacion"></x-textarea>

                        <x-flex class="justify-end mt-4 gap-2">
                            <x-button variant="secondary" wire:click="cancelarRegistro">
                                Cancelar
                            </x-button>
                            <x-button wire:click="guardarPrimasComisiones" wire:loading.attr="disabled">
                                <i class="fa fa-save"></i> Generar Montos
                            </x-button>
                        </x-flex>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Historial: navegación entre meses ya registrados --}}
        <div class="md:w-[24rem]">
            <x-h3 class="mb-3">Meses registrados</x-h3>
            <x-card class="overflow-hidden divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($fechasRegistradas as $fechaRegistrada)
                            <a href="#" wire:click.prevent="cambiarFechaA('{{ $fechaRegistrada }}')" class="block w-full px-4 py-3 text-center font-medium cursor-pointer transition-colors
                                        {{ $fechaRegistrada === $fecha_inicio
                    ? 'bg-zinc-100 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100'
                    : 'hover:bg-zinc-50 dark:hover:bg-zinc-900/50 text-zinc-500' }}">
                                {{ $fechaRegistrada }}
                            </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-zinc-400">
                        Aún no hay meses registrados.
                    </p>
                @endforelse
            </x-card>
        </div>
    </div>

    <x-loading wire:loading />
</div>