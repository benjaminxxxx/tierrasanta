<div {{ $attributes->merge(['class' => 'focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800']) }}>
    <x-flex>
        <i class="fa fa-check-circle text-white mr-3 font-2xl"></i>
        <div>
            {{ $slot }}
        </div>
    </x-flex>
</div>