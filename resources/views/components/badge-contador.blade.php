@props(['cantidad', 'sufijo', 'colorClass' => 'bg-gray-100'])
@if ($cantidad > 0)
    <x-badge class="{{ $colorClass }} text-black">{{ $cantidad }}{{ $sufijo }}</x-badge>
@else
    <x-badge class="bg-gray-100 text-black">0{{ $sufijo }}</x-badge>
@endif