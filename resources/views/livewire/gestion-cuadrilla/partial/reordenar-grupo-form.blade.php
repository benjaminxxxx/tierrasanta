<x-dialog-modal wire:model.live="mostrarReordenarGrupoForm">
    <x-slot name="title">
        Reordenar grupos
    </x-slot>

    <x-slot name="content">
        <div x-data="reordenarGrupoCuadrilla(@entangle('listaGrupos'))" class="space-y-2">
            <template x-for="(grupo, index) in listaGrupos" :key="grupo.codigo">
                <div class="flex items-center justify-between p-2 rounded" :style="`background-color: ${grupo.color}`">
                    <span x-text="grupo.nombre" class="font-medium text-gray-800"></span>
                    <div class="space-x-1">
                        <button @click="subir(index)" class="text-blue-600 hover:text-blue-800">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button @click="bajar(index)" class="text-blue-600 hover:text-blue-800">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="$set('mostrarReordenarGrupoForm', false)" wire:loading.attr="disabled">
            Cancelar
        </x-secondary-button>

        <x-button class="ms-3" wire:click="registrarOrdenGrupal" wire:loading.attr="disabled">
            Guardar nuevo orden
        </x-button>
    </x-slot>
</x-dialog-modal>

@script
<script>
    Alpine.data('reordenarGrupoCuadrilla', (listaGrupos) => ({
        listaGrupos,

        subir(index) {
            if (index === 0) return;
            [this.listaGrupos[index - 1], this.listaGrupos[index]] =
                [this.listaGrupos[index], this.listaGrupos[index - 1]];
        },

        bajar(index) {
            if (index === this.listaGrupos.length - 1) return;
            [this.listaGrupos[index + 1], this.listaGrupos[index]] =
                [this.listaGrupos[index], this.listaGrupos[index + 1]];
        }
    }));
</script>
@endscript