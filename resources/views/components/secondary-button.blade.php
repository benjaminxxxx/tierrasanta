<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-block cursor-pointer rounded-lg border border-primary bg-white py-3 px-5 font-medium text-darken transition hover:bg-opacity-90']) }}>
    {{ $slot }}
</button>
