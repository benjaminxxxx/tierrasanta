@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-3 md:px-8 py-4">
        <div class="my-5">
            <x-title>
                {{ $title }}
            </x-title>
        </div>

        <div class="mt-4 text-sm">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 border-t border-border text-end rounded-b-lg gap-3">
        {{ $footer }}
    </div>

</x-modal>
