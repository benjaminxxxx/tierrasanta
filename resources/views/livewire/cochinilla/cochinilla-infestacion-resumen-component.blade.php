<x-card>
    @if (!empty($resumen))

        <div class="p-2">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">
                Resumen — {{ \Carbon\Carbon::create(null, $mes)->translatedFormat('F') }} {{ $anio }}
            </h3>

            {{-- KPIs principales --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-muted rounded-lg p-3 text-center">
                    <p class="text-xs text-muted-foreground">Total registros</p>
                    <p class="text-2xl font-bold">{{ $resumen['total'] }}</p>
                </div>
                <div class="bg-muted rounded-lg p-3 text-center">
                    <p class="text-xs text-muted-foreground">KG Madres</p>
                    <p class="text-2xl font-bold">{{ number_format($resumen['total_kg_madres'], 2) }}</p>
                </div>
                <div class="bg-muted rounded-lg p-3 text-center">
                    <p class="text-xs text-muted-foreground">KG Madres / Ha</p>
                    <p class="text-2xl font-bold">{{ number_format($resumen['kg_madres_por_ha'], 2) }}</p>
                </div>
                <div class="bg-muted rounded-lg p-3 text-center">
                    <p class="text-xs text-muted-foreground">Total Infestadores</p>
                    <p class="text-2xl font-bold">{{ number_format($resumen['total_infestadores']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Por campo --}}
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-2">Por campo</p>
                    <div class="space-y-2">
                        @foreach ($resumen['por_campo'] as $item)
                            <div>
                                <div class="flex justify-between text-xs mb-0.5">
                                    <span class="font-medium">{{ $item['campo'] }}</span>
                                    <span class="text-muted-foreground">{{ $item['cantidad'] }} inf. ·
                                        {{ $item['porcentaje'] }}%</span>
                                </div>
                                <div class="w-full bg-muted rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full"
                                        style="width: {{ $item['porcentaje'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Por método --}}
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-2">Por método</p>
                    <div class="space-y-2">
                        @foreach ($resumen['por_metodo'] as $item)
                            <div>
                                <div class="flex justify-between text-xs mb-0.5">
                                    <span class="font-medium">{{ $item['metodo'] }}</span>
                                    <span class="text-muted-foreground">{{ $item['cantidad'] }} ·
                                        {{ $item['porcentaje'] }}%</span>
                                </div>
                                <div class="w-full bg-muted rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full"
                                        style="width: {{ $item['porcentaje'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Por tipo --}}
                <div>
                    <p class="text-xs font-semibold uppercase text-muted-foreground mb-2">Por tipo</p>
                    <div class="space-y-2">
                        @foreach ($resumen['por_tipo'] as $item)
                            <div>
                                <div class="flex justify-between text-xs mb-0.5">
                                    <span class="font-medium">{{ $item['tipo'] }}</span>
                                    <span class="text-muted-foreground">{{ $item['cantidad'] }} ·
                                        {{ $item['porcentaje'] }}%</span>
                                </div>
                                <div class="w-full bg-muted rounded-full h-1.5">
                                    <div class="bg-emerald-500 h-1.5 rounded-full"
                                        style="width: {{ $item['porcentaje'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    @endif

</x-card>
