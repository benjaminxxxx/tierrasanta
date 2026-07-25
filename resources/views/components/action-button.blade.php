@props([
    'title',
    'description',
])

<button 
    type="button"
    {{ $attributes->merge([
        'class' => 'bg-card text-card-foreground rounded-xl border border-border shadow-sm p-5 text-left hover:border-primary hover:shadow-md transition-all w-full cursor-pointer'
    ]) }}
>
    <h3 class="font-semibold text-foreground mb-1">{{ $title }}</h3>
    <p class="text-sm text-muted-foreground">{{ $description }}</p>
</button>