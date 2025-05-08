<div>
    @php
        function barra_porcentaje($porcentaje, $color = 'bg-green-500')
        {
            if ($porcentaje == 0) {
                return '';
            }

            $width = min($porcentaje, 100); // máximo 100%
            return "<div class='w-full bg-gray-200 rounded h-3 overflow-hidden'>
                    <div class='$color h-full' style='width: {$width}%'></div>
                </div>
                <small class='text-gray-600'>" .
                number_format($porcentaje, 1) .
                '%' .
                '</small>';
        }
    @endphp

    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <x-h2>
                Análisis de Proceso de Cochinilla
            </x-h2>
        </x-slot>

        <x-slot name="content">

            <x-alert>
                <x-icons.info-icon class="h-4 w-4" />
                <x-alert-description>
                    Visualización del proceso de cochinilla desde su ingreso hasta su procesamiento de venteado y
                    filtrado.
                    La merma es un campo informativo que muestra la pérdida de material entre etapas.
                </x-alert-description>
            </x-alert>
            @if ($resumen)
                {{-- Material Útil --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <x-card2>
                        <x-card-header class="pb-2">
                            <x-card-title>Material Útil Venteado</x-card-title>
                            <x-card-description>Limpia + Polvillo</x-card-description>
                        </x-card-header>
                        <x-card-content>
                            <div class="text-2xl font-bold text-emerald-500">
                                {{ number_format($resumen->material_util_venteado, 2) }} Kl
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ number_format($resumen->material_util_venteado_porcentaje, 2) }}% del total
                            </div>
                        </x-card-content>
                    </x-card2>

                    <x-card2>
                        <x-card-header class="pb-2">
                            <x-card-title>Material Útil Filtrado</x-card-title>
                            <x-card-description>1ra + 2da + 3ra</x-card-description>
                        </x-card-header>
                        <x-card-content>
                            <div class="text-2xl font-bold text-emerald-500">
                                {{ number_format($resumen->material_util_filtrado, 2) }} Kl
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ number_format($resumen->material_util_filtrado_porcentaje, 2) }}% del total
                            </div>
                        </x-card-content>
                    </x-card2>
                </div>
                {{-- Merma Total --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <x-card2>
                        <x-card-header class="pb-2">
                            <x-card-title>Merma Ingreso → Venteado</x-card-title>
                            <x-card-description>Pérdida antes del venteado</x-card-description>
                        </x-card-header>
                        <x-card-content>
                            <div class="text-2xl font-bold text-red-500">{{ number_format($resumen->merma_ingreso_venteado, 2) }} KI</div>
                            <div class="text-sm text-muted-foreground">
                                {{ number_format($resumen->merma_ingreso_venteado_porcentaje, 2) }}% del total
                            </div>
                        </x-card-content>
                    </x-card2>

                    <x-card2>
                        <x-card-header class="pb-2">
                            <x-card-title>Merma Venteado → Filtrado</x-card-title>
                            <x-card-description>Pérdida durante el filtrado</x-card-description>
                        </x-card-header>
                        <x-card-content>
                            <div class="text-2xl font-bold text-red-500">{{ number_format($resumen->merma_venteado_filtrado, 2) }} KI</div>
                            <div class="text-sm text-muted-foreground">
                                {{ number_format($resumen->merma_venteado_filtrado_porcentaje, 2) }}% del venteado
                            </div>
                        </x-card-content>
                    </x-card2>
                    <x-card2>
                        <x-card-header class="pb-2">
                            <x-card-title>Merma Total</x-card-title>
                            <x-card-description>Ingreso → Filtrado</x-card-description>
                        </x-card-header>
                        <x-card-content>
                            <div class="text-2xl font-bold text-red-500">
                                {{ number_format($resumen->merma_ingreso_filtrado, 2) }} KI
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ number_format($resumen->merma_ingreso_filtrado_porcentaje, 2) }}% del total
                            </div>
                        </x-card-content>
                    </x-card2>
                </div>

                <div class="mt-4">
                    <x-tabs default-value="flow">
                        <x-tabs-list class="mb-4">
                            <x-tabs-trigger value="flow">Flujo del Proceso</x-tabs-trigger>
                            <x-tabs-trigger value="detalle">Detalle Histórico</x-tabs-trigger>
                            <x-tabs-trigger value="bar">Gráfico de Barras</x-tabs-trigger>
                            <x-tabs-trigger value="table">Tabla Detallada</x-tabs-trigger>
                        </x-tabs-list>
                    
                        <x-tabs-content value="flow">
                            <x-card2>
                                <x-card-header>
                                    <x-card-title>Flujo del Proceso de Cochinilla</x-card-title>
                                    <x-card-description>Visualización del flujo de material y mermas entre etapas</x-card-description>
                                </x-card-header>
                                <x-card-content>
                                    {{-- Contenido para el gráfico de flujo aquí --}}
                                    @include('livewire.cochinilla_ingreso_mapa_component.partial-1-flujo-del-proceso')
                                </x-card-content>
                            </x-card2>
                        </x-tabs-content>
                    
                        <x-tabs-content value="detalle">
                            <x-card2>
                                <x-card-header>
                                    <x-card-title>Detalle Histórico del Proceso de Cochinilla</x-card-title>
                                    <x-card-description>Visualización de los detalles de cada proceso</x-card-description>
                                </x-card-header>
                                <x-card-content>
                                    {{-- Contenido para el diagrama Sankey aquí --}}
                                    @include('livewire.cochinilla_ingreso_mapa_component.partial-2-detalle')
                                </x-card-content>
                            </x-card2>
                        </x-tabs-content>
                    
                        <x-tabs-content value="bar">
                            <x-card2>
                                <x-card-header>
                                    <x-card-title>Gráfico de Barras</x-card-title>
                                    <x-card-description>Comparación visual de cantidades por etapa</x-card-description>
                                </x-card-header>
                                <x-card-content>
                                    {{-- Contenido para el gráfico de barras aquí --}}
                                    @include('livewire.cochinilla_ingreso_mapa_component.partial-3-grafico-barras')
                                </x-card-content>
                            </x-card2>
                        </x-tabs-content>
                    
                        <x-tabs-content value="table">
                            <x-card2>
                                <x-card-header>
                                    <x-card-title>Tabla Detallada</x-card-title>
                                    <x-card-description>Datos numéricos detallados del proceso</x-card-description>
                                </x-card-header>
                                <x-card-content>
                                    {{-- Contenido de tabla detallada aquí --}}
                                    @include('livewire.cochinilla_ingreso_mapa_component.partial-4-resumen')
                                </x-card-content>
                            </x-card2>
                        </x-tabs-content>
                    </x-tabs>
                    
                </div>
            @endif

            
        </x-slot>

        <x-slot name="footer">

        </x-slot>
    </x-dialog-modal>



</div>
