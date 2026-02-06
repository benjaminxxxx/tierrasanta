{{-- components/table.blade.php --}}
<div {{ $attributes->merge(['class' => "relative overflow-x-auto rounded-lg border border-border bg-background backdrop-blur-sm"]) }}>
    <table class="w-full text-sm text-left rtl:text-right text-foreground">
        {{-- Encabezado: Diferente del contenido usando un tono sólido y fuente pesada --}}
        <thead class="text-xs uppercase font-bold tracking-wider text-muted-foreground bg-muted border-b border-border">
            {{ $thead }}
        </thead>
        <tbody class="divide-y divide-border">
            {{ $tbody }}
        </tbody>
        @if(isset($tfoot))
            <tfoot class="border-t border-border bg-muted font-semibold">
                {{ $tfoot }}
            </tfoot>
        @endif
    </table>
</div>