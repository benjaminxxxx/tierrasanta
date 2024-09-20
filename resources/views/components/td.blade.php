@props(['value'])

<td {{ $attributes->merge(['class' => 'text-center border border-slate-400 p-2']) }}>
    {{ $value ?? $slot }}
</td>
