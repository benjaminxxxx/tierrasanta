<div {{ $attributes->merge([
    'class' => '
        flex items-center p-4 rounded-lg border
        text-yellow-800 bg-yellow-100 border-yellow-300
        dark:text-yellow-200 dark:bg-yellow-600 dark:border-yellow-500
    '
]) }}>
    <i class="fa fa-exclamation-triangle 
        text-yellow-600 mr-3 
        dark:text-yellow-300"></i>

    <div class="dark:text-yellow-100">
        {{ $slot }}
    </div>
</div>
