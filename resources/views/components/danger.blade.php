<div {{ $attributes->merge([
    'class' => '
        flex items-center py-2 px-4 shadow-sm rounded-lg border
        text-red-800 bg-red-100 border-red-300
        dark:text-red-200 dark:bg-red-900/30 dark:border-red-800
    '
]) }}>
    <i class="fa fa-circle-exclamation 
        text-red-600 mr-3 
        dark:text-red-400"></i>

    <div class="text-sm font-bold uppercase tracking-wider">
        {{ $slot }}
    </div>
</div>