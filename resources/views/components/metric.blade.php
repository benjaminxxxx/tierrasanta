@props(['label', 'value'])

<x-card>
    <p class="text-xs text-card-foreground mb-1">{{ $label }}</p>
    <p class="text-2xl font-medium">{{ $value }}</p>
</x-card>