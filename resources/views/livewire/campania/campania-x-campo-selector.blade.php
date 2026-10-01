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
            @include('livewire.campania.partials.campania-x-campo-selector-opciones')
        </x-flex>
    </x-card>

    @if ($campaniaSeleccionada)
        @can(\App\Constants\Permisos::CAMPAÑA_POR_CAMPO_VER)
            <div class="inline-flex p-1 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                @foreach (\App\Livewire\Campania\CampaniaCampoSelectorComponent::PESTANIAS as $clave => $etiqueta)
                    <button type="button" wire:click="$set('pestania', '{{ $clave }}')" @class([
                        'px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150',
                        'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm' => $pestania === $clave,
                        'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' => $pestania !== $clave,
                    ])>{{ $etiqueta }}</button>
                @endforeach
            </div>

            @if ($pestania === 'costos')
                <livewire:campania.campania-costo-produccion-component :campania-id="(int) $campaniaSeleccionada"
                    wire:key="Costos{{ $campaniaSeleccionada }}" />
            @else
            <livewire:campania.campania-por-campo-informe-component :campania="$campaniaSeleccionada"
                wire:key="Camp{{ $campaniaSeleccionada }}" />
            @endif
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
    {{-- Modales de campaña: se montan solo en las páginas que los usan --}}
    <livewire:campania.campania-ficha-component />
    <livewire:campania.campania-cerrar-component />
</div>
@script
<script>
    Alpine.data('campaniaXCampo', () => ({
        init() {
            Livewire.on('campania-cambiada', ({
                id
            }) => {

                const base = '{{ route('campania.por_campo') }}';
                const nuevaUrl = `${base}/${id}`;
                window.history.pushState({}, '', nuevaUrl);
            })
        }
    }));
</script>
@endscript