@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <x-title>
            {{ $title }}
        </x-title>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            {{ $content }}
        </div>
    </div>

    <div
        class="flex flex-row justify-end px-6 py-4 bg-white dark:bg-gray-800 border-t border-gray-700 text-end rounded-b-lg gap-3">
        {{ $footer }}
    </div>

</x-modal>