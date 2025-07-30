@props([
    'name',
    'logo' => '',
    'text' => '',
    'active' => false,
])

@php
    $classes =
        $active ?? false
        ? 'w-full h-10 flex items-center transition-all duration-200 hover:bg-gray-700 hover:text-white bg-gray-700 text-white'
        : 'w-full h-10 flex items-center transition-all duration-200 hover:bg-gray-700 hover:text-white text-gray-200';
    
@endphp

<div>
    <button
        x-on:click="toggleMenu('{{ $name }}')"
        class="{{ $classes }}"
        :class="isExpanded ? 'justify-start px-3' : 'justify-center px-0'"
    >
        <i class="{{ $logo }} h-5 w-5 flex-shrink-0"></i>
        <template x-if="isExpanded">
            <div class="flex items-center ml-3 truncate w-full">
                <span>{{ $text }}</span>
                <i class="fa fa-chevron-right ml-auto h-4 w-4 transform transition-transform duration-200"
                   :class="isOpen('{{ $name }}') ? 'rotate-90' : ''"></i>
            </div>
        </template>
    </button>

    <template x-if="isExpanded && isOpen('{{ $name }}')">
        <div class="space-y-1">
            {{ $slot }}
        </div>
    </template>
</div>
