<div>

    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Lista Completa de Nutrientes
        </x-h3>
    </div>
    <x-card>
        <x-spacing>

            <x-table>
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
        </x-spacing>
    </x-card>
</div>
