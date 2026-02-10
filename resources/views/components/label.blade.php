@props(['for' => null, 'value' => null])

<label {{ $attributes->merge([
    'for' => $for,
    'class' => 'text-sm font-medium text-foreground leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 select-none'
]) }}>
    {{ $slot->isEmpty() ? $value : $slot }}
</label>
