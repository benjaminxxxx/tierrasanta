<div x-data="calculos">
    <!--MODULO COCHINILLA INFESTACION FORM-->
    
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Infestación de Cochinilla
        </x-slot>

        <x-slot name="content">

            @if ($campania)
                <x-success class="mb-3">
                    <p>
                        Campo
                        {{ $campania->campo ?? '' }}
                    </p>
                    <p>
                        Campaña
                        {{ $campania->nombre_campania ?? '' }}
                    </p>
                    <p>
                        Variedad
                        {{ $campania->variedad_tuna ?? '' }}
                    </p>
                    <p>
                        Fecha de Inicio
                        {{ $campania->fecha_inicio ?? '' }}
                    </p>
                    <p>
                        Fecha Siembra
                        {{ $campania->fecha_siembra ?? '' }}
                    </p>
                </x-success>
            @else
                <x-warning class="mb-3">
                    No hay campañas registradas en este campo y esta fecha
                </x-warning>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                <x-group-field>
                    <x-select label="Tipo" wire:model.live="tipo_infestacion" error="campoSeleccionado">
                        <option value="">Seleccionar tipo</option>
                        <option value="infestacion">Infestación</option>
                        <option value="reinfestacion">Reinfestación</option>
                    </x-select>
                </x-group-field>
                <x-group-field>
                    @if ($tipo_infestacion == 'infestacion')
                        <x-input-date label="Fecha de infestación" wire:model.live="fecha" />
                    @elseif ($tipo_infestacion == 'reinfestacion')
                        <x-input-date label="Fecha de reinfestación" wire:model.live="fecha" />
                    @endif
                </x-group-field>
                <x-group-field>
                    <x-select-campo label="Campo destino" wire:model.live="campoSeleccionado"
                        error="campoSeleccionado" />
                </x-group-field>
                <x-group-field>
                    <x-select-campo label="Campo de origen" wire:model.live="campoSeleccionadoOrigen"
                        error="campoSeleccionadoOrigen" />
                </x-group-field>
                <x-group-field>
                    <x-input-number label="Área" wire:model="area" x-model="area" />
                </x-group-field>
                <x-group-field>
                    <x-input-number label="Kg madres" wire:model="kg_madres" x-model="kg_madres" />
                </x-group-field>
                <x-group-field>
                    <x-input-number label="Kg madres x Ha." wire:model="kg_madres_por_ha" x-model="kg_madres_ha"
                        class="!bg-gray-100" readonly />
                </x-group-field>
                <x-group-field>
                    <x-select label="Método de infestación" wire:model.live="metodo" error="metodo">
                        <option value="">Seleccionar método</option>
                        <option value="carton">Cartón</option>
                        <option value="tubo">Tubo</option>
                        <option value="malla">Malla</option>
                    </x-select>
                </x-group-field>
            </div>
            @php
                $unidad = match ($metodo) {
                    'carton' => 'Unds. por caja',
                    'tubo' => 'Unds. por caja',
                    'malla' => 'Unds. por bolsa',
                    default => 'Unds.',
                };

                $cantidad = match ($metodo) {
                    'carton' => 'N° de cajas',
                    'tubo' => 'N° de cajas',
                    'malla' => 'N° de bolsas',
                    default => 'Cantidad',
                };

                $nombre = match ($metodo) {
                    'carton' => 'Infestadores',
                    'tubo' => 'Tubos',
                    'malla' => 'Mallas',
                    default => 'Infestadores',
                };

                $porUnidad = match ($metodo) {
                    'carton' => 'Madres / Infes.',
                    'tubo' => 'Madres / tubo',
                    'malla' => 'Madres / malla',
                    default => 'Madres / Infes.',
                };

                $porHa = match ($metodo) {
                    'carton' => 'Infes. / Ha.',
                    'tubo' => 'Tubos / Ha.',
                    'malla' => 'Mallas / Ha.',
                    default => 'Infes. / Ha.',
                };

                $fondo = match ($metodo) {
                    'carton' => 'bg-yellow-50',
                    'tubo' => 'bg-blue-50',
                    'malla' => 'bg-green-50',
                    default => 'bg-white',
                };
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-3 {{ $fondo }}">
                <x-group-field>
                    <x-input-number :label="$unidad" wire:model="capacidad_envase"
                        @input="recalcularDesdeCapacidad()" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$cantidad" wire:model="numero_envases"
                        @input="recalcularDesdeNumeroEnvases()" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$nombre" wire:model="infestadores" @input="recalcularDesdeInfestadores()" />
                </x-group-field>
                <x-group-field>
                    <x-input-string :label="$porUnidad" wire:model="madres_por_infestador"
                        x-bind:value="madres_por_infestador" readonly class="!bg-gray-100" />
                </x-group-field>
                <x-group-field>
                    <x-input-string :label="$porHa" wire:model="infestadores_por_ha" x-bind:value="infestadores_por_ha"
                        readonly class="!bg-gray-100" />
                </x-group-field>
            </div>
            <div class="my-4 p-5 rounded overflow-hidden rounded-lg bg-gray-100">
                <x-h3>Registro de ingresos relacionados</x-h3>
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center">
                                N°
                            </x-th>
                            <x-th class="text-center">
                                Lote
                            </x-th>
                            <x-th class="text-center">
                                Campo
                            </x-th>
                            <x-th class="text-center">
                                Fecha de ingreso
                            </x-th>
                            <x-th class="text-center">
                                Total Kilos
                            </x-th>
                            <x-th class="text-center">
                                Kg Filtrado
                            </x-th>
                            <x-th class="text-center">
                                Descripción
                            </x-th>
                            <x-th class="text-center">
                                Kilos a usar
                            </x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($cochinillaIngresoRelacionados as $indice => $cochinilla)
                            <x-tr>
                                <x-td class="text-center">{{ $indice + 1 }}</x-td>
                                <x-td class="text-center">{{ $cochinilla->lote }}</x-td>
                                <x-td class="text-center">{{ $cochinilla->campo }}</x-td>
                                <x-td class="text-center">{{ formatear_fecha($cochinilla->fecha) }}</x-td>
                                <x-td class="text-center">{{ formatear_numero($cochinilla->total_kilos) }}</x-td>
                                <x-td
                                    class="text-center">{{ formatear_numero($cochinilla->filtrado_primera + $cochinilla->filtrado_segunda + $cochinilla->filtrado_tercera) }}</x-td>
                                <x-td class="text-center">Ingreso sin infestaciones</x-td>
                                <x-td class="text-center">
                                    <x-flex>
                                        <input type="number" wire:model="kgAsignados.{{ $cochinilla->id }}"
                                        class="w-20 border rounded px-2 py-1 text-right" min="0"
                                        max="{{ $cochinilla->stock_disponible ?? $cochinilla->total_kilos }}" step="0.01" />
                                    <input type="checkbox" wire:model="ingresosSeleccionados" value="{{ $cochinilla->id }}"
                                        class="ml-2" />
                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>

                </x-table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-form-buttons action="registrar" :id="$cochinillaInfestacionId" />
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="registrar" />
    <x-loading wire:loading wire:target="tipo_infestacion" />
    <x-loading wire:loading wire:target="campoSeleccionado" />
    <x-loading wire:loading wire:target="metodo" />
    <x-loading wire:loading wire:target="campoSeleccionadoOrigen" />

</div>
@script
<script>
    Alpine.data('calculos', () => ({
        kg_madres_ha: @entangle('kg_madres_ha'),
        capacidad_envase: @entangle('capacidad_envase'),
        numero_envases: @entangle('numero_envases'),
        infestadores: @entangle('infestadores'),
        kg_madres: @entangle('kg_madres'),
        area: @entangle('area'),

        get madres_por_infestador() {
            if (!this.infestadores || this.infestadores == 0) return '0gr.';

            const valor = ((this.kg_madres / this.infestadores) * 100000);
            const formateado = new Intl.NumberFormat('en-US').format(Math.round(valor));

            return `${formateado}gr.`;
        },

        get infestadores_por_ha() {
            if (!this.area || this.area === 0) return '0';
            const valor = Math.round(this.infestadores / this.area);
            return new Intl.NumberFormat('en-US').format(valor); // → 1,000
        },
        recalcularKgMadresHa() {
            if (!this.area || this.area == 0) return 0;
            this.kg_madres_ha = (this.kg_madres / this.area).toFixed(2);
        },
        recalcularDesdeCapacidad() {
            this.infestadores = this.capacidad_envase * this.numero_envases;
            $wire.set('infestadores', this.infestadores);
        },

        recalcularDesdeNumeroEnvases() {
            this.infestadores = this.capacidad_envase * this.numero_envases;
            $wire.set('infestadores', this.infestadores);
        },

        recalcularDesdeInfestadores() {
            if (this.numero_envases != 0) {
                this.capacidad_envase = (this.infestadores / this.numero_envases).toFixed(2);
                $wire.set('capacidad_envase', this.capacidad_envase);
            }
        },
        init() {
            this.$watch('area', () => this.recalcularKgMadresHa());
            this.$watch('kg_madres', () => this.recalcularKgMadresHa());
        }
    }));
</script>
@endscript