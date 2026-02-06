{{-- components/card.blade.php --}}
<div 
    {{ $attributes->merge([
        'class' => 'bg-card text-card-foreground rounded-xl border border-border shadow-sm p-2 md:p-5'
    ]) }}>
    {{ $slot }}
</div>