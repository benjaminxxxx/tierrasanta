@props([
    'type' => 'button',
])
<x-button {{ $attributes->merge(['type' => $type]) }}>
    <i class="fa fa-save"></i> {{ $slot ?? $value }}
</x-button>