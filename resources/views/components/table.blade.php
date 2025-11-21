<div {{ $attributes->merge(['class' => "relative overflow-x-auto shadow-md sm:rounded-lg border-1 dark:border-gray-500"]) }}>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            {{ $thead }}
        </thead>
        <tbody>
            {{ $tbody }}
        </tbody>
        <tfoot>
            {{ $tfoot??'' }}
        </tfoot>
    </table>
</div>
