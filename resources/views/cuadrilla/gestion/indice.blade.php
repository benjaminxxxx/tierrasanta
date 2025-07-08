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
            @foreach($modules as $module)
                <x-card2 class="hover:shadow-lg transition-shadow">
                    <div class="p-4 flex items-center space-x-3">
                        <div class="p-2 rounded-lg {{ $module['color'] }}">
                            <i class="fas {{ $module['icon'] }} text-white"></i>
                        </div>
                        <x-h3>
                            {{ $module['title'] }}
                        </x-h3>
                    </div>
                    <div class="p-4">
                        <x-label class="text-lg mb-4">{{ $module['description'] }}</x-label>
                        <x-button-a href="{{ route($module['route']) }}">
                            Acceder al Módulo
                        </x-button-a>
                    </div>
                </x-card2>
            @endforeach
        </div>
    </div>
</x-app-layout>