@props(['value'])

<h2 {{ $attributes->merge(['class' => 'font-medium text-muted-foreground text-md']) }}>
    {{ $value ?? $slot }}
</h2>