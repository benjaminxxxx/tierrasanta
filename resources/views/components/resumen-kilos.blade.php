@props(['titulo', 'icono', 'valor', 'columnas' => [], 'subiconos' => [], 'subvalores' => []])

<x-table2>
    <thead>
        <x-tr2 class="bg-gray-50">
            <x-th2 :colspan="count($columnas) ?: 3">{{ $titulo }}</x-th2>
        </x-tr2>
    </thead>
    <tbody>
        <x-tr2 class="align-bottom">
            <x-td2 :colspan="count($columnas) ?: 3">
                <i {!! icono($icono, 'text-green-500') !!}></i>
            </x-td2>
        </x-tr2>
        <x-tr2>
            <x-td2 :colspan="count($columnas) ?: 3">
                <b class="text-lg">{{ number_format($valor, 2) }}Kl</b>
            </x-td2>
        </x-tr2>

        @if (count($columnas))
            <x-tr2 class="bg-gray-50">
                @foreach ($columnas as $col)
                    <x-th2>{{ $col }}</x-th2>
                @endforeach
            </x-tr2>
            <x-tr2 class="align-bottom">
                @foreach ($subiconos as $i => $px)
                    <x-td2>
                        <i {!! icono($px, $i) !!}></i>
                    </x-td2>
                @endforeach
            </x-tr2>
            <x-tr2>
                @foreach ($subvalores as $val)
                    <x-td2>
                        <b class="text-lg">{{ number_format($val, 2) }}Kl</b>
                    </x-td2>
                @endforeach
            </x-tr2>
        @endif
    </tbody>
</x-table2>
