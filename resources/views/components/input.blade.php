@props([
    'disabled' => false,
    'id' => null,
    'type' => 'text',
    'label' => null,
    'help' => null,
])

@php
    $id = $id ?? md5($attributes->wire('model'));
@endphp

@if ($type === 'checkbox')
    <div class="flex">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" type="checkbox" aria-describedby="{{ $id }}-help"
                {{ $disabled ? 'disabled' : '' }}
                {!! $attributes->merge([
                    'class' =>
                        'w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600',
                ]) !!} />
        </div>
        <div class="ms-2 text-sm">
            @if ($label)
                <label for="{{ $id }}" class="font-medium text-gray-900 dark:text-gray-300">
                    {{ $label }}
                </label>
            @endif

            @if ($help)
                <p id="{{ $id }}-help" class="text-xs font-normal text-gray-500 dark:text-gray-300">
                    {{ $help }}
                </p>
            @endif
        </div>
    </div>
@else
    <div>
        <input id="{{ $id }}" type="{{ $type }}" {{ $disabled ? 'disabled' : '' }}
            {!! $attributes->merge([
                'class' =>
                    'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
            ]) !!} />

        @if ($help)
            <p id="{{ $id }}-help" class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-300">
                {{ $help }}
            </p>
        @endif
    </div>
@endif
