@props(['value'])

<button
    @click="setSelected('{{ $value }}')"
    :class="{
        'bg-gray-100 text-black font-semibold dark:bg-graydark dark:text-whiten': selected === '{{ $value }}',
        'text-gray-500 dark:text-white font-semibold  hover:text-black dark:hover:text-white': selected !== '{{ $value }}'
    }"
    class="px-4 py-2 rounded-md transition"
    type="button"
>
    {{ $slot }}
</button>
