<div x-data="campaniaXCampo" class="space-y-4">
   
    <x-flex class="justify-between">
        <x-breadcrumb :items="$breadcrumb"/>
        @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
            <x-button @click="$wire.dispatch('registroCampania')">
                <i class="fa fa-plus"></i> Registrar Nueva Campaña
            </x-button>
        @endcan

    </x-flex>
    <x-card>
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo wire:model.live="campoSeleccionado" class="w-auto" label="Seleccionar Campo" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña" class="w-auto">
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
        @can(\App\Constants\Permisos::CAMPAÑA_POR_CAMPO_VER)
            <livewire:gestion-campania.campania-por-campo-informe-component :campania="$campaniaSeleccionada"
                wire:key="Camp{{ $campaniaSeleccionada }}" />
        @else
            <x-danger>
                No tienes permisos para ver la información de la campaña. Por favor, contacta al administrador.
            </x-danger>
        @endcan

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