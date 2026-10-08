<div class="space-y-4">
    <div>
        <x-title>Configuración</x-title>
        <x-subtitle>Parámetros de negocio. No son reglas estrictas: el sistema los usa para sugerir lo que normalmente toca.</x-subtitle>
    </div>

    <x-card class="space-y-4 max-w-3xl">
        <div>
            <h3 class="font-semibold text-foreground">Evaluaciones de brotes por campaña</h3>
            <p class="text-sm text-muted-foreground">
                Días promedio desde el inicio de la campaña en que se hace cada evaluación de brotes (normalmente la primera a los 35–40 días y unas 4
                antes de la infestación). Si una campaña sin infestación pasa esos días sin su evaluación, aparece una sugerencia en tareas pendientes.
                Con fecha de infestación ya no se sugieren: solo se avisa si no tiene ninguna evaluación registrada.
            </p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            @forelse ($diasBrotes as $i => $dia)
                <div class="w-32" wire:key="dia-{{ $i }}">
                    <x-label>{{ $i + 1 }}ª evaluación</x-label>
                    <div class="flex items-center gap-1">
                        <x-input type="number" min="1" wire:model="diasBrotes.{{ $i }}" class="text-right" />
                        <button type="button" class="text-red-600 px-1" title="Quitar" wire:click="quitarEvaluacion({{ $i }})"><i class="fa fa-times"></i></button>
                    </div>
                    <span class="text-xs text-muted-foreground">días</span>
                </div>
            @empty
                <p class="text-sm text-muted-foreground">Sin evaluaciones configuradas: no se sugiere ninguna.</p>
            @endforelse
            <x-button variant="secondary" wire:click="agregarEvaluacion"><i class="fa fa-plus"></i> Agregar evaluación</x-button>
        </div>
        @error('dias') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="w-72">
            <x-input type="number" min="1" wire:model="diasMaximo" label="Dejar de sugerir después de (días desde el inicio)" error="dias_maximo" />
            <p class="text-xs text-muted-foreground mt-1">Evita sugerencias para campañas antiguas que siguen abiertas.</p>
        </div>

        <div class="flex justify-end">
            <x-button wire:click="guardarBrotes"><i class="fa fa-save"></i> Guardar</x-button>
        </div>
    </x-card>
    <x-card class="space-y-4 max-w-3xl">
        <div>
            <h3 class="font-semibold text-foreground">Evaluaciones de infestación</h3>
            <p class="text-sm text-muted-foreground">
                Días después de la infestación en que se cuenta la cochinilla por penca. Con ellos se arma el texto de la pantalla de evaluación
                (Evaluación → Infestación) y, si una campaña pasa esos días sin la evaluación registrada, aparece un aviso en tareas pendientes.
                La pantalla registra hasta {{ \App\Services\Campania\Etapa\CampaniaEtapaReglas::EVALUACIONES_INFESTACION_REGISTRABLES }} evaluaciones por campaña.
            </p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            @forelse ($diasInfestacion as $i => $dia)
                <div class="w-32" wire:key="dia-inf-{{ $i }}">
                    <x-label>{{ $i + 1 }}ª evaluación</x-label>
                    <div class="flex items-center gap-1">
                        <x-input type="number" min="1" wire:model="diasInfestacion.{{ $i }}" class="text-right" />
                        <button type="button" class="text-red-600 px-1" title="Quitar" wire:click="quitarEvaluacionInfestacion({{ $i }})"><i class="fa fa-times"></i></button>
                    </div>
                    <span class="text-xs text-muted-foreground">días</span>
                </div>
            @empty
                <p class="text-sm text-muted-foreground">Sin evaluaciones configuradas: no se avisa ninguna.</p>
            @endforelse
            <x-button variant="secondary" wire:click="agregarEvaluacionInfestacion"><i class="fa fa-plus"></i> Agregar evaluación</x-button>
        </div>
        @error('dias_infestacion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="avisarInfestacionCerradas" class="rounded">
            Avisar también en campañas cerradas
        </label>

        <div class="flex justify-end">
            <x-button wire:click="guardarInfestacion"><i class="fa fa-save"></i> Guardar</x-button>
        </div>
    </x-card>
    <x-card class="space-y-4 max-w-3xl">
        <div>
            <h3 class="font-semibold text-foreground">Labores: reutilizar códigos</h3>
            <p class="text-sm text-muted-foreground">
                Un código de labor que ya no se usa puede pasar a ser otra labor desde una fecha (Campo → Labores → Reasignar), sin cambiar sus
                registros ni reportes antiguos. Se sugieren los códigos sin registros hace más de estos meses.
            </p>
        </div>
        <div class="flex items-end gap-3">
            <div class="w-48">
                <x-input type="number" min="1" wire:model="mesesReutilizar" label="Meses sin uso" error="meses_reutilizar" />
            </div>
            <x-button wire:click="guardarLabores"><i class="fa fa-save"></i> Guardar</x-button>
        </div>
    </x-card>
    <x-card class="space-y-4 max-w-3xl">
        <div>
            <h3 class="font-semibold text-foreground">Cochinilla: tipos de ingreso vendibles</h3>
            <p class="text-sm text-muted-foreground">
                La mamá que se cosecha para infestar no se vende: su cochinilla vuelve después como un ingreso de infestadores (con otro peso) y
                ese es el que se vende. Venderla también la duplicaría. Si un lote va parte a venta y parte a infestación, regístralo con sublotes
                de tipos distintos (p. ej. "Mama – Venta" y "Poda – Mama"): solo los kilos de los sublotes vendibles se ofrecen para la venta.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach ($tiposCochinilla as $codigo => $descripcion)
                <label class="flex items-center gap-2 text-sm" wire:key="vend-{{ $codigo }}">
                    <input type="checkbox" wire:model="vendibles.{{ $codigo }}" class="rounded">
                    {{ $descripcion }}
                </label>
            @endforeach
        </div>
        <div class="flex justify-end">
            <x-button wire:click="guardarVendibles"><i class="fa fa-save"></i> Guardar</x-button>
        </div>
    </x-card>
    <x-loading wire:loading wire:target="guardarBrotes,guardarInfestacion,guardarLabores,guardarVendibles" />
</div>
