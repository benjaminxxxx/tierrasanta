<div x-data="accordionTable('accordion-campania')" 
     @accordion-open-all.window="openAll(['info-general','poblacion-plantas','brotes','infestacion','reinfestacion','nutrientes'])"
     @accordion-close-all.window="closeAll()">
    <x-card class="mt-4">
        <div class="flex justify-end gap-3 mb-3">
            <flux:button size="xs" variant="primary" @click="$dispatch('accordion-open-all')">
                Abrir todo
            </flux:button>

            <flux:button size="xs" variant="filled" @click="$dispatch('accordion-close-all')">
                Cerrar todo
            </flux:button>
        </div>

        <x-table class="!w-auto">
            <x-slot name="thead">

            </x-slot>
            <x-slot name="tbody">
                <div>
                    <x-table class="!w-auto">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th-header class="w-64"></x-th-header>
                                <x-th-header class="text-right w-28"></x-th-header>
                                <x-th-header class="text-right w-28"></x-th-header>
                                <x-th-header class="w-40"></x-th-header>
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">

                            {{-- GRUPO 1: Informacion General --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'info-general',
                                'titulo' => 'INFORMACIÓN GENERAL',
                                'partial' =>
                                    'livewire.gestion-campania.partials.campania-x-campo-selector-informacion-general',
                            ])

                            {{-- GRUPO 2: Población --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'poblacion-plantas',
                                'titulo' => 'POBLACIÓN DE PLANTAS',
                                'partial' =>
                                    'livewire.gestion-campania.partials.campania-x-campo-poblacion-plantas',
                            ])

                            {{-- GRUPO 3: Brotes --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'brotes',
                                'titulo' => 'BROTES',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-brotes',
                            ])

                            {{-- GRUPO 4: Infestación --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'infestacion',
                                'titulo' => 'INFESTACIÓN',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-infestacion',
                            ])

                            {{-- GRUPO 5: Reinfestación --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'reinfestacion',
                                'titulo' => 'REINFESTACIÓN',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-reinfestacion',
                            ])

                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'cosecha',
                                'titulo' => 'COSECHA',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-cosecha',
                            ])

                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'riego',
                                'titulo' => 'RIEGO',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-riego',
                            ])

                            {{-- GRUPO 6: Nutrientes --}}
                            @include('livewire.gestion-campania.partials.accordion-section', [
                                'id' => 'nutrientes',
                                'titulo' => 'NUTRIENTES',
                                'partial' => 'livewire.gestion-campania.partials.campania-x-campo-nutrientes',
                            ])
                        </x-slot>
                    </x-table>
                </div>

            </x-slot>
        </x-table>
    </x-card>

    <livewire:evaluaciones.evaluacion-poblacion-planta-form-component />
    <livewire:evaluaciones.evaluacion-brotes-form-component />
    <livewire:evaluaciones.evaluacion-infestacion-form-component />
    <livewire:evaluaciones.evaluacion-reinfestacion-form-component />
    <livewire:gestion-riego.riego-campania-form-component />
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('accordionTable', (storageKey) => ({
            // almacenamos IDs abiertos en array persistente
            openIds: JSON.parse(sessionStorage.getItem(storageKey) || '[]'),

            toggle(id) {
                if (this.openIds.includes(id)) {
                    this.openIds = this.openIds.filter(x => x !== id);
                } else {
                    this.openIds.push(id);
                }
                sessionStorage.setItem(storageKey, JSON.stringify(this.openIds));
            },

            isOpen(id) {
                return this.openIds.includes(id);
            },

            openAll(ids) {
                this.openIds = ids;
                sessionStorage.setItem(storageKey, JSON.stringify(this.openIds));
            },

            closeAll() {
                this.openIds = [];
                sessionStorage.setItem(storageKey, JSON.stringify(this.openIds));
            }
        }));
    </script>
@endscript
