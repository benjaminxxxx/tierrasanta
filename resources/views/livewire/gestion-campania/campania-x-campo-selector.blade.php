<div x-data="campaniaXCampo">
    <x-flex class="justify-between">
        <x-title>
            Campañas por Campo
        </x-title>
        <x-button @click="$wire.dispatch('registroCampania')">
            <i class="fa fa-plus"></i> Registrar Nueva Campaña
        </x-button>
    </x-flex>
    <x-card class="mt-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo wire:model.live="campoSeleccionado" class="max-w-[5rem]" label="Seleccionar Campo" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña">
                        <option value="">Elegir Campaña</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>

                @endif
            </x-flex>
            @include('livewire.gestion-campania.partials.campania-x-campo-selector-opciones')
        </x-flex>
    </x-card>

    @if ($campaniaSeleccionada)
        <livewire:gestion-campania.campania-por-campo-informe-component :campania="$campaniaSeleccionada"
            wire:key="Camp{{ $campaniaSeleccionada }}" />
    @else
        <x-card class="mt-4">
            <x-label>
                Seleccionar Campaña
            </x-label>
        </x-card>
    @endif

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('campaniaXCampo', () => ({
            init() {
                Livewire.on('campania-cambiada', ({
                    id
                }) => {

                    const base = '{{ route('campania.x.campo') }}';
                    const nuevaUrl = `${base}/${id}`;
                    window.history.pushState({}, '', nuevaUrl);
                })
            }
        }));
    </script>
@endscript
