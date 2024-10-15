<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-block cursor-pointer rounded-lg border border-meta-4 bg-meta-4 py-2 px-4 font-medium text-white transition hover:bg-opacity-90']) }}>
    {{ $slot }}
</button>
