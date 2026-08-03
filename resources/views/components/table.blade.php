@props([
    'noScroll' => false,
])

<div
    {{ $attributes->merge([
        'class' => 'relative rounded-lg border border-zinc-200 dark:border-zinc-800  ' .
            ($noScroll ? '' : 'overflow-x-auto'),
    ]) }}>
    <table class="w-full text-sm text-left rtl:text-right text-zinc-700 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 ">
        <thead class="sticky top-0 z-10 text-xs uppercase tracking-wider divide-y divide-zinc-300 dark:divide-zinc-700 border-b border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900">
            {{ $thead }}
        </thead>

        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            {{ $tbody }}
        </tbody>

        @if (isset($tfoot))
            <tfoot class="border-t border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900 font-semibold text-zinc-700 dark:text-zinc-300">
                {{ $tfoot }}
            </tfoot>
        @endif
    </table>
</div>