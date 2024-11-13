<div {{ $attributes->merge(['class' => "relative overflow-x-auto shadow-md sm:rounded-lg"]) }}>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-primaryTextDark uppercase bg-gray-50 dark:bg-primaryDark dark:text-gray-400">
            {{ $thead }}
        </thead>
        <tbody>
            {{ $tbody }}
        </tbody>
    </table>
</div>
