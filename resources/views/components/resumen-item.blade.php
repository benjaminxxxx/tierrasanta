{{-- -resources/views/components/resumen-item.blade.php --}}
@props(['label', 'value' => null])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between px-4 py-2 bg-muted rounded-md border border-border gap-3']) }}>
    <span class="text-xs font-bold text-muted-foreground uppercase">
        {{ $label }}
    </span>

    <div class="flex items-center gap-2">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            <span class="text-base font-semibold text-card-foreground">
                {{ $value }}
            </span>
        @endif
    </div>
</div>