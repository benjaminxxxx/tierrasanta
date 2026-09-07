@props(['orientation' => 'horizontal'])

<div {{ $attributes->merge(['class' => $orientation === 'vertical' ? 'flex flex-col gap-1 shrink-0 w-56' : 'flex gap-2']) }}>
    {{ $slot }}
</div>