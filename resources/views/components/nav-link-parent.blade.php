@props(['name', 'logo' => '', 'text' => '', 'active' => false])

@php
    $classes =
        $active ?? false
        ? 'rounded w-full h-10 flex items-center transition-all duration-200 
                bg-gray-100 text-gray-900 hover:bg-gray-100 
                dark:bg-gray-700 dark:text-white dark:hover:bg-gray-700'
        : 'rounded w-full h-10 flex items-center transition-all duration-200 
                text-gray-700 hover:bg-gray-100 
                dark:text-gray-200 dark:hover:bg-gray-700';
@endphp


<div>
    <button x-on:click="toggleMenu('{{ $name }}')" class="{{ $classes }}"
        :class="isExpanded ? 'justify-start px-3' : 'justify-center px-0'">
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
        <div>
            {{ $slot }}
        </div>
    </template>
</div>