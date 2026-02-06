@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   class="block pl-12 pr-4 py-1 text-sm transition 
       {{ $active 
           ? 'font-semibold text-card-foreground' 
           : 'text-muted-foreground hover:text-gray-900 hover:text-muted-foreground' }}">
    {{ $slot }}
</a>
