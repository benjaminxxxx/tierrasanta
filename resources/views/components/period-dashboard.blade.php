@props(['stats' => []])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    @forelse($stats as $stat)
        <x-card>
            <div class="flex items-center justify-between mb-3">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">
                        {{ $stat['code'] }}
                    </span>
                    <h3 class="text-sm font-semibold text-card-foreground dark:text-gray-200">
                        {{ $stat['label'] }}
                    </h3>
                </div>
                <div 
                    class="w-4 h-4 rounded-full border border-black/10 shadow-sm" 
                    style="background-color: {{ $stat['color'] }}"
                ></div>
            </div>
            
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-primary dark:text-white">
                    {{ $stat['count'] }}
                </span>
                <span class="text-xs text-muted-foreground text-gray-500 dark:text-gray-400">
                    {{ $stat['count'] === 1 ? 'empleado' : 'empleados' }}
                </span>
            </div>
            
            <p class="text-[11px] text-gray-400 mt-2 italic">
                Activos actualmente
            </p>
        </x-card>
    @empty
        <div class="col-span-full p-8 text-center border-2 border-dashed border-gray-200 rounded-xl">
            <p class="text-gray-500">No hay periodos de asistencia activos el día de hoy.</p>
        </div>
    @endforelse
</div>