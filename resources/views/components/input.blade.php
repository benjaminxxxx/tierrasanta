{{-- components/input.blade.php --}}
@props([
    'disabled' => false,
    'id' => null,
    'type' => 'text',
    'label' => null,
    'help' => null,
    'size' => 'base', // xs, sm, base, lg
    'error' => null,
])

@php
    $id = $id ?? 'input-' . Str::uuid();
    $model = $attributes->whereStartsWith('wire:model')->first();

    $isDisabled = (bool) ($disabled || $attributes->has('disabled'));
    $isReadOnly = $attributes->has('readonly');

    // Mapeo de alturas idéntico al select
    $sizeClasses = match ($size) {
        'xs'   => 'h-7 px-2 text-xs',
        'sm'   => 'h-8 px-2 text-sm',
        'base' => 'h-9 px-3 text-sm',
        'lg'   => 'h-11 px-4 text-base',
        default => 'h-9 px-3 text-sm',
    };

    // Estilo base Shadcn
    $baseClasses = 'w-full rounded-md border border-input bg-background text-foreground shadow-xs transition-colors outline-none 
                    file:border-0 file:bg-transparent file:text-sm file:font-medium
                    placeholder:text-muted-foreground
                    focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]
                    disabled:cursor-not-allowed disabled:opacity-50';

    $errorClasses = $error ? 'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20' : '';

    $classes = trim("{$baseClasses} {$sizeClasses} {$errorClasses}");
@endphp

<x-group-field>
    @if ($type === 'checkbox')
        <div class="flex items-center gap-2">
            <input id="{{ $id }}" type="checkbox" {{ $isDisabled ? 'disabled' : '' }} 
                {!! $attributes->merge(['class' => 'h-4 w-4 rounded border-input bg-background text-primary focus:ring-ring/50']) !!} 
            />
            @if ($label)
                <x-label for="{{ $id }}" class="cursor-pointer">{{ $label }}</x-label>
            @endif
        </div>
    @else
        @if ($label)
            <x-label for="{{ $id }}">{{ $label }}</x-label>
        @endif

        <input id="{{ $id }}" type="{{ $type }}" 
            {{ $isDisabled ? 'disabled' : '' }} 
            {{ $isReadOnly && !$isDisabled ? 'readonly' : '' }}
            {!! $attributes->except(['disabled', 'readonly'])->merge(['class' => $classes]) !!} 
        />

        @if ($help)
            <p id="{{ $id }}-help" class="text-xs text-muted-foreground">{{ $help }}</p>
        @endif

        @if ($error && $model)
            <x-input-error for="{{ $model }}" class="text-xs text-destructive" />
        @endif
    @endif
</x-group-field>