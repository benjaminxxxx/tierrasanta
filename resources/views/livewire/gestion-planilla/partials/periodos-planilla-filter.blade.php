<x-card class="my-4">

    {{-- Header --}}
    <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-filter text-indigo-600 text-sm"></i>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            Filtros
        </h3>
    </div>

    <x-flex class="!items-end">
        <x-select-anios wire:model.live="anio" class="w-auto"/>
        <x-select-meses wire:model.live="mes" class="w-auto"/>
    </x-flex>
</x-card>
