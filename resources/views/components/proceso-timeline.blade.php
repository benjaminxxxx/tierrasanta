@props(['pasoActivo' => 1, 'vertical' => false])

<div
    x-data="{ pasoActivo: {{ $pasoActivo }} }"
    @class([
        'flex items-start w-full'           => !$vertical,
        'flex flex-col'                     => $vertical,
    ])
>
    {{ $slot }}
</div>