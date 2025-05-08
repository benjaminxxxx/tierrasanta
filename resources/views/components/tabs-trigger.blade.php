@props(['value'])

<button
    @click="selected = '{{ $value }}'"
    :class="{
        'bg-gray-100 text-black font-semibold': selected === '{{ $value }}',
        'text-gray-500 hover:text-black': selected !== '{{ $value }}'
    }"
    class="px-4 py-2 rounded-md transition"
    type="button"
>
    {{ $slot }}
</button>
