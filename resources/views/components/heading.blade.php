@props([
    'title' => 'Sin titulo',
    'subtitle' => '',
])


<div>
    <x-title>
        {{ $title??'' }}
    </x-title>
    <x-subtitle>
        {{ $subtitle??'' }}
    </x-subtitle>
</div>
