@props([
    'status' => 'activo',
])

@php
    $styles = [
        'activo'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'en_prueba'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'finalizado' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'suspendido' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    ];

    $labels = [
        'activo'     => 'Activo',
        'en_prueba'  => 'En Prueba',
        'finalizado' => 'Finalizado',
        'suspendido' => 'Suspendido',
    ];

    $badgeStyle = $styles[$status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    $badgeLabel = $labels[$status] ?? ucfirst($status);
@endphp

<span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $badgeStyle }}">
    {{ $badgeLabel }}
</span>