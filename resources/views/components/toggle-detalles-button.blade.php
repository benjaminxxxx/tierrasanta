@props(['id'])
<x-button variant="ghost" @click="abiertos[{{ $id }}] = !abiertos[{{ $id }}]">
    <i class="fa fa-chevron-down transition-transform duration-200"
        :class="abiertos[{{ $id }}] ? 'rotate-180' : ''"></i>
</x-button>