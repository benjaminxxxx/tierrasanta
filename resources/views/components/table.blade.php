<div {{ $attributes->merge(['class' => "shadow-md sm:rounded-lg relative overflow-x-auto"]) }}>
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs dark:text-primaryTextDark uppercase bg-gray-100 dark:bg-primaryDark">
            {{ $thead }}
        </thead>
        <tbody>
            {{ $tbody }}
        </tbody>
    </table>
</div>
