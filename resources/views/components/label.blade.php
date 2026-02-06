{{-- components/label.blade.php --}}
@props(['for' => null])
<label {{ $attributes->merge(['for' => $for, 'class' => 'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 select-none']) }}>
    {{ $slot }}
</label>