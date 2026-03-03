@props([
    'title' => 'Indicaciones',
])

<div {{ $attributes->merge([
    'class' => '
        p-5 rounded-xl border shadow-sm
        bg-blue-50/60 text-blue-900 border-blue-300
        dark:bg-blue-900/40 dark:text-blue-100 dark:border-blue-600
    '
]) }}>
    <div class="flex items-start gap-3">
        
        {{-- Icono informativo --}}
        <div>
            <i class="fa fa-info-circle 
                text-blue-600 dark:text-blue-300 text-2xl mt-1"></i>
        </div>

        <div class="flex-1">

            {{-- Título --}}
            <h3 class="font-bold text-lg mb-2 
                text-blue-800 dark:text-blue-200">
                {{ $title }}
            </h3>

            {{-- Cuerpo (slot). Puedes incluir HTML libremente) --}}
            <div class="prose prose-sm dark:prose-invert max-w-none">
                {{ $slot }}
            </div>

        </div>
    </div>
</div>