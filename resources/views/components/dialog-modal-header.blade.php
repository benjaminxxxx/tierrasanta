@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div>
        <div class="text-lg font-medium text-white bg-primary dark:text-gray-100">
            <x-spacingx>
                {{ $title }}
            </x-spacingx>
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <x-spacingx>
                {{ $content }}
            </x-spacingx>
        </div>
    </div>

    <div class="flex flex-row justify-end bg-slate-100 dark:bg-gray-800 text-end">
        <x-spacingx>
            {{ $footer }}
        </x-spacingx>
    </div>
</x-modal>
