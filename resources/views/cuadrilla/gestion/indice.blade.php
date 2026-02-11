<x-app-layout>
    <div class="container mx-auto p-6">
        <div class="mb-8">
            <x-title class="mb-2">
                Sistema de Gestión de Cuadrilleros
            </x-title>
            <x-subtitle>
                Administra trabajadores, actividades, grupos de pago y bonificaciones
            </x-subtitle>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-gray-700">
                        <i class="fas fa-hard-hat text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Lista de Cuadrilleros</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Consulta y administra los cuadrilleros registrados</x-label>
                    <x-button-a class="mt-4" href="{{ route('cuadrilla.cuadrilleros') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>

            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-gray-500">
                        <i class="fas fa-users text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Grupos de Cuadrillas</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Gestiona los grupos de trabajo de la cuadrilla</x-label>
                    <x-button-a class="mt-4" href="{{ route('cuadrilla.grupos') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>

            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-orange-500">
                        <i class="fas fa-calendar-alt text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Reporte Semanal</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Crear y gestionar períodos de pago por grupo</x-label>
                    <x-button-a class="mt-4" href="{{ route('gestion_cuadrilleros.reporte-semanal.index') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>

            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-blue-500">
                        <i class="fas fa-clock text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Detallar Registro Diario</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Buscar cuadrilleros y asignar actividades diarias</x-label>
                    <x-button-a class="mt-4" href="{{ route('gestion_cuadrilleros.registro-diario.index') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>

            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-indigo-500">
                        <i class="fas fa-chart-bar text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Bonificaciones</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Registrar producción y calcular bonos</x-label>
                    <x-button-a class="mt-4" href="{{ route('gestion_cuadrilleros.bonificaciones.index') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>

            <x-card>
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-red-500">
                        <i class="fas fa-file-excel text-white w-5 h-5 text-center"></i>
                    </div>
                    <x-h3>Resumen General</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Informe General de Cuadrilla</x-label>
                    <x-button-a class="mt-4" href="{{ route('gestion_cuadrilleros.resumen_general.index') }}">
                        Acceder
                    </x-button-a>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
