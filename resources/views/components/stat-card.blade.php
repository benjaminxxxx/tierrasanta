@props([
    'icon',
    'iconColor' => 'text-gray-500',
    'label',
    'value',
    'trend',
])

<x-card class="p-6">
    <div class="flex items-start justify-between mb-4">
        <div class="flex-1">
            <p class="text-sm text-muted-foreground mb-2">{{ $label }}</p>
            <p class="text-3xl font-bold text-foreground">{{ $value }}</p>
        </div>
        <div class="text-2xl {{ $iconColor }}">
            <i class="{{ $icon }}"></i>
        </div>
    </div>
    <p class="text-xs text-muted-foreground">{{ $trend }}</p>
</x-card>