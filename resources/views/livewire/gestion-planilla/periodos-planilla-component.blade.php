<div x-data="periodosPlanilla">
    <x-heading title="Gestión de Períodos"
        subtitle="Administra vacaciones, licencias y otros períodos de los trabajadores" />

    <x-period-dashboard :stats="$stats" />
    {{-- Floating Action Button --}}
    @if (!$mostrarFormularioPeriodo)
        <button wire:click="nuevoRegistroPeriodo" title="Agregar nuevo período"
            class="fixed bottom-6 right-6 z-50
               w-16 h-16 rounded-full
               bg-indigo-600 text-white
               shadow-lg
               transition-all
               hover:bg-indigo-600/90 hover:scale-110
               flex items-center justify-center">
            <i class="fa-solid fa-plus text-xl"></i>
        </button>
    @endif
    @include('livewire.gestion-planilla.partials.periodos-planilla-filter')
    @include('livewire.gestion-planilla.partials.periodos-planilla-table')
    @include('livewire.gestion-planilla.partials.periodos-planilla-form')

    <x-loading wire:loading/>
</div>

@script
    <script>
        Alpine.data('periodosPlanilla', () => ({
            confirmarEliminacion(periodoId) {
                Swal.fire({
                    title: 'Eliminar período',
                    text: 'Esta acción quedará registrada en el historial.',
                    icon: 'warning',

                    input: 'textarea',
                    inputLabel: 'Motivo de la eliminación',
                    inputPlaceholder: 'Describe el motivo...',

                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#056A70',
                    cancelButtonColor: '#999999',

                    preConfirm: (motivo) => {
                        if (!motivo) {
                            Swal.showValidationMessage('El motivo es obligatorio');
                            return false;
                        }
                        return motivo;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 🔥 DISPATCH A LIVEWIRE
                        Livewire.dispatch('eliminarPeriodoConfirmado', {
                            id: periodoId,
                            motivo: result.value
                        });
                    }
                });
            }
        }));
    </script>
@endscript
