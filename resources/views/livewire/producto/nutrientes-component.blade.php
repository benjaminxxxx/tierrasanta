<div class="space-y-4">
    <div>
        <x-title>
            Lista Completa de Nutrientes
        </x-title>
    </div>
    @can(\App\Constants\Permisos::INSUMO_NUTRIENTE_VER)
        <x-card>

            <x-table class="mt-4">
                <x-slot name="thead">
                    <tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Nutriente
                        </x-th>
                        <x-th>
                            Simbolo
                        </x-th>
                        <x-th class="text-center">
                            Unidad
                        </x-th>
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($nutrientes as $indice => $nutriente)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            <x-td class="text-xl font-bold text-center">{{ $nutriente->nombre }}</x-td>
                            <x-td>{{ $nutriente->descripcion }}</x-td>
                            <x-td class="text-center">{{ $nutriente->unidad }}</x-td>
                        </x-tr>
                    @endforeach
                </x-slot>

            </x-table>
        </x-card>
    @else
        <x-danger>
            No tiene permiso para ver la siguiente información.
        </x-danger>
    @endcan
    {{-- Excel de recursos: solo las hojas de esta página (DESIGN.md) --}}
    <livewire:sistema.sistema-libro-excel-component libro="recursos" :hojas="['nutrientes']" :flotante="true" :permiso="\App\Constants\Permisos::INSUMO_NUTRIENTE" />
</div>