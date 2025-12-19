{{-- resources/views/components/legend.blade.php --}}
@props([
    'color' => 'bg-gray-300',
    'label' => '—',
])

<div class="flex items-center gap-2">
    <div class="w-3 h-3 {{ $color }} rounded-sm"></div>
    <span>{{ $label }}</span>
</div>
