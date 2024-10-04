<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-block cursor-pointer rounded-lg border border-slate-400 bg-white dark:bg-primaryDark dark:text-primaryTextDark py-3 px-5 font-medium text-darken transition hover:bg-opacity-90']) }}>
    {{ $slot }}
</button>
