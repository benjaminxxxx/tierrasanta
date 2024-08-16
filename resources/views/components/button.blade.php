<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-block cursor-pointer rounded-lg border border-primary bg-primary py-3 px-5 font-medium text-white transition hover:bg-opacity-90']) }}>
    {{ $slot }}
</button>
