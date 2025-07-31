@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   class="block pl-12 pr-4 py-1 text-sm transition 
       {{ $active 
           ? 'font-semibold text-gray-900 dark:text-white' 
           : 'text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' }}">
    {{ $slot }}
</a>
