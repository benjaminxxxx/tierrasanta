<div>
    <x-dialog-modal-header wire:model="abrirSeleccionarCampos" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <div class="">
                    Seleccionar Campos
                </div>
                <div class="flex-shrink-0">
                    <button wire:click="$set('abrirSeleccionarCampos', false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-4 gap-2">
                @foreach ($campos as $campo)
                    @php
                        // Generar un identificador único de 5 números aleatorios
                        $randomId = rand(10000, 99999);
                    @endphp
                    <div class="category col-span-4 md:col-span-2 lg:col-span-1">
                        <div class="flex items-center mt-4 mb-2">
                            <x-checkbox id="campo_{{ $campo->nombre }}_{{ $randomId }}"
                                wire:model="camposSeleccionados" type="checkbox" value="{{ $campo->nombre }}" />
                            <x-label class="!font-bold ml-2 !mb-0" for="campo_{{ $campo->nombre }}_{{ $randomId }}">
                                {{ $campo->nombre }}</x-label>
                        </div>
                        @if ($campo->hijos->isNotEmpty())
                            <div class="ml-4">
                                @foreach ($campo->hijos as $hijo)
                                    @php
                                        // Generar otro identificador único para los subcampos
                                        $randomSubId = rand(10000, 99999);
                                    @endphp
                                    <div class="flex items-center mb-2 subcategory">
                                        <x-checkbox id="subcampo_{{ $hijo->nombre }}_{{ $randomSubId }}"
                                            wire:model="camposSeleccionados" type="checkbox"
                                            value="{{ $hijo->nombre }}" />
                                        <x-label for="subcampo_{{ $hijo->nombre }}_{{ $randomSubId }}"
                                            class="ml-2 !mb-0">
                                            {{ $hijo->nombre }}</x-label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

            </div>

        </x-slot>
        <x-slot name="footer">
            <x-button type="button" wire:click="guardarSeleccion">Aceptar</x-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
