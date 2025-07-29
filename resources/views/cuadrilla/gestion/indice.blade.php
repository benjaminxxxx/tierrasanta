<x-app-layout>
    <div class="container mx-auto p-6">
        <div class="mb-8">
            <x-h3 class="text-3xl font-bold text-gray-900 mb-2">
                Sistema de Gestión de Cuadrilleros
            </x-h3>
            <x-label class="text-gray-600">
                Administra trabajadores, actividades, grupos de pago y bonificaciones
            </x-label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-gray-700">
                        <i class="fas fa-hard-hat text-white"></i>
                    </div>
                    <x-h3>Lista de Cuadrilleros</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Consulta y administra los cuadrilleros registrados</x-label>
                    <x-button-a href="{{ route('cuadrilla.cuadrilleros') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>

            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-gray-500">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <x-h3>Grupos de Cuadrillas</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Gestiona los grupos de trabajo de la cuadrilla</x-label>
                    <x-button-a href="{{ route('cuadrilla.grupos') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>

            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-orange-500">
                        <i class="fas fa-calendar-alt text-white"></i>
                    </div>
                    <x-h3>Reporte Semanal</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Crear y gestionar períodos de pago por grupo</x-label>
                    <x-button-a href="{{ route('gestion_cuadrilleros.reporte-semanal.index') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>

            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-blue-500">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <x-h3>Detallar Registro Diario</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Buscar cuadrilleros y asignar actividades diarias</x-label>
                    <x-button-a href="{{ route('gestion_cuadrilleros.registro-diario.index') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>

            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-indigo-500">
                        <i class="fas fa-chart-bar text-white"></i>
                    </div>
                    <x-h3>Bonificaciones</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Registrar producción y calcular bonos</x-label>
                    <x-button-a href="{{ route('gestion_cuadrilleros.bonificaciones.index') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>

            <x-card2 class="hover:shadow-lg transition-shadow">
                <div class="p-4 flex items-center space-x-3">
                    <div class="p-2 rounded-lg bg-red-500">
                        <i class="fas fa-dollar-sign text-white"></i>
                    </div>
                    <x-h3>Pago de Cuadrilla</x-h3>
                </div>
                <div class="p-4">
                    <x-label class="text-lg mb-4">Procesar pagos y generar reportes</x-label>
                    <x-button-a href="{{ route('gestion_cuadrilleros.pagos.index') }}">
                        Acceder al Módulo
                    </x-button-a>
                </div>
            </x-card2>
        </div>
    </div>
</x-app-layout>
