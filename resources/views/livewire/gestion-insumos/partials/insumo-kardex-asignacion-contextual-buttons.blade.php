<div
    x-show="hasChanges"
    x-transition
    class="fixed bottom-6 right-6 z-30 inline-flex gap-3 pointer-events-auto"
>
    <x-button
        @click="cancelarCambios"
        variant="danger"
        size="lg"
    >
        <i class="fa fa-remove"></i> Deshacer Cambios
    </x-button>

    <x-button
        @click="confirmarCambios"
        variant="success"
        size="lg"
    >
        <i class="fa fa-check"></i> Confirmar Asignaciones
    </x-button>
</div>
