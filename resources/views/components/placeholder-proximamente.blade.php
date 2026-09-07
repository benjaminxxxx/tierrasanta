@props(['titulo'])

<div class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
    <i class="fa fa-hourglass-half text-3xl mb-3 opacity-50"></i>
    <p class="font-semibold">{{ $titulo }}</p>
    <p class="text-sm">Próximamente</p>
</div>