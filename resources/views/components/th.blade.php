@props(['value', 'sticky' => false, 'sortable' => null, 'direction' => null, 'active' => false, 'level' => 2])

@php
    $level = (int) $level;

    $tone = $level === 1
        ? ['text' => 'text-zinc-700 dark:text-zinc-200 font-extrabold', 'bg' => 'bg-zinc-200 dark:bg-zinc-950']
        : ['text' => 'text-zinc-500 dark:text-zinc-400 font-bold', 'bg' => 'bg-zinc-100 dark:bg-zinc-900'];
@endphp

<th scope="col"
    {{ $attributes->merge([
        'class' =>
            'px-3 py-2 text-center align-middle ' . $tone['text'] . ' ' .
            ($sticky ? 'sticky left-0 z-20 ' . $tone['bg'] . ' ' : '') .
            ($sortable ? 'cursor-pointer hover:bg-zinc-300/60 dark:hover:bg-zinc-800 transition-colors' : ''),
    ]) }}
    @if ($sortable) wire:click="sortBy('{{ $sortable }}')" @endif>
    <div class="flex items-center justify-center gap-1">
        {{ $value ?? $slot }}

        @if ($sortable)
            @if ($active)
                @if ($direction === 'asc')
                    <i class="fa fa-arrow-up"></i>
                @else
                    <i class="fa fa-arrow-down"></i>
                @endif
            @else
                <svg class="w-3 h-3 ms-1.5 opacity-50" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8.574 11.024h6.852a2.075 2.075 0 0 0 1.847-1.086 1.9 1.9 0 0 0-.11-1.986L13.736 2.9a2.122 2.122 0 0 0-3.472 0L6.837 7.952a1.9 1.9 0 0 0-.11 1.986 2.074 2.074 0 0 0 1.847 1.086Zm6.852 1.952H8.574a2.072 2.072 0 0 0-1.847 1.087 1.9 1.9 0 0 0 .11 1.985l3.426 5.05a2.123 2.123 0 0 0 3.472 0l3.427-5.05a1.9 1.9 0 0 0 .11-1.985 2.074 2.074 0 0 0-1.846-1.087Z" />
                </svg>
            @endif
        @endif
    </div>
</th>