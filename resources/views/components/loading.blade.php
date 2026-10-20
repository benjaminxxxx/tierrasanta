{{-- Overlay de carga. No aparece mientras se escribe en un campo en vivo o en un buscador (ver app.js: escribiendo-en-vivo). --}}
<div {{ $attributes->merge(['class' => "x-loading-overlay fixed inset-0 bg-zinc-900 bg-opacity-50 flex items-center justify-center z-[10000]"]) }}>
    <div class="w-screen h-screen flex items-center justify-center">
        <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full text-white"></div>
    </div>
</div>
