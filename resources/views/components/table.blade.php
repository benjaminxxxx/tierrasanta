<div {{ $attributes->merge(['class' => "overflow-x-auto"]) }}>
    <table class="w-full text-sm">
        <thead>
            {{ $thead }}
        </thead>
        <tbody>
            {{ $tbody }}
        </tbody>
        <tfoot>
            {{ $tfoot??'' }}
        </tfoot>
    </table>
</div>
