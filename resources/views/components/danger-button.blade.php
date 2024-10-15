<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-block cursor-pointer rounded-lg border border-red-700 bg-red-600 py-2 px-4 font-medium text-white transition hover:bg-opacity-90']) }}>
    {{ $slot }}
</button>
