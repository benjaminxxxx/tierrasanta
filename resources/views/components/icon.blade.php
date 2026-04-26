<span {{ $attributes->merge([
    'class' => 'w-9.5 h-9.5 flex items-center justify-center rounded-lg shrink-0 bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
]) }}>
    {{ $slot }}
</span>