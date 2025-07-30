@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   class="block pl-12 pr-4 py-2 text-sm transition hover:text-white
       {{ $active ? 'text-white font-semibold' : 'text-gray-200' }}">
    {{ $slot }}
</a>
