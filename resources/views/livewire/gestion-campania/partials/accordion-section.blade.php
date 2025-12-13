{{-- Fila del encabezado del grupo --}}
<x-tr class="cursor-pointer bg-gray-100 dark:bg-gray-700 hover:bg-gray-200" @click="toggle('{{ $id }}')">
    <x-th class="text-left">
        <div class="flex items-center justify-between w-full">
            <span class="font-bold text-gray-800 dark:text-gray-100">
                {{ $titulo }}
            </span>


        </div>
    </x-th>
    <x-th class="text-right">
        DATOS
    </x-th>
    <x-th class="text-right">
        DATOS/HA
    </x-th>
    <x-th class="text-right">
        <svg :class="isOpen('{{ $id }}') ? 'rotate-180' : 'rotate-0'" class="w-4 h-4 transition-transform inline-block"
            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
            <path stroke="currentColor" stroke-width="2" d="M9 5L5 1 1 5" />
        </svg>
    </x-th>
</x-tr>

{{-- Filas internas colapsables --}}
<tbody x-show="isOpen('{{ $id }}')" x-collapse>
    @include($partial)
</tbody>