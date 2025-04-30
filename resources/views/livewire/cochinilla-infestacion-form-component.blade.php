<div x-data="calculos">
    <!--MODULO COCHINILLA INFESTACION FORM-->
    <x-loading wire:loading wire:target="registrar" />
    <x-loading wire:loading wire:target="tipo_infestacion" />
    <x-loading wire:loading wire:target="campoSeleccionado" />
    <x-loading wire:loading wire:target="metodo" />
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="2xl">
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
                    <x-select-campo label="Campo de origen" wire:model="campoSeleccionadoOrigen"
                        error="campoSeleccionadoOrigen" />
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
                    <x-input-number :label="$unidad" wire:model="capacidad_envase" @input="recalcularDesdeCapacidad()" />
                    <x-input-error for="capacidad_envase" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$cantidad" wire:model="numero_envases" @input="recalcularDesdeNumeroEnvases()"/>
                    <x-input-error for="numero_envases" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$nombre" wire:model="infestadores" @input="recalcularDesdeInfestadores()"/>
                    <x-input-error for="infestadores" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$porUnidad" wire:model="madres_por_infestador" x-bind:value="madres_por_infestador" readonly
                        class="!bg-gray-100" />
                </x-group-field>
                <x-group-field>
                    <x-input-number :label="$porHa" wire:model="infestadores_por_ha" x-bind:value="infestadores_por_ha" readonly class="!bg-gray-100" />
                </x-group-field>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-form-buttons action="registrar" :id="$cochinillaInfestacionId" />
        </x-slot>
    </x-dialog-modal>
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
                if (!this.infestadores || this.infestadores == 0) return 0;
                return (this.kg_madres / this.infestadores).toFixed(6);
            },

            get infestadores_por_ha() {
                if (!this.area || this.area == 0) return 0;
                return (this.infestadores / this.area).toFixed(2);
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
