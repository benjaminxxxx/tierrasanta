<div>
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Módulo de ventas
        </x-h3>
    </x-flex>

    <x-tabs default-value="entrega_venta">
        <x-card2>
            <x-tabs-list class="mb-4">
                <x-tabs-trigger value="entrega_venta">Entrega de venta</x-tabs-trigger>
                <x-tabs-trigger value="registrar_venta">Registrar venta</x-tabs-trigger>
                <x-tabs-trigger value="evaluacion_brotes">Evaluación de Brotes</x-tabs-trigger>
                <x-tabs-trigger value="infestacion">Infestación</x-tabs-trigger>
            </x-tabs-list>
            </x-card>


            <x-tabs-content value="entrega_venta">
                <livewire:cochinilla_ventas.cochinilla-venta-registro-entrega-component />
            </x-tabs-content>

            <x-tabs-content value="registrar_venta">
                <!--<li vewire:cochinilla_ventas.cochinilla-venta-registro-component />-->
            </x-tabs-content>

            <x-tabs-content value="evaluacion_brotes">
                f
            </x-tabs-content>

            <x-tabs-content value="infestacion">
                g
            </x-tabs-content>
    </x-tabs>

</div>