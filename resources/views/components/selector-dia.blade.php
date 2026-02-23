<div 
    {{-- 
    ============================================================
     SELECTOR DE FECHA CON FLATPICKR + LIVEWIRE + ALPINE
    ============================================================
    
     ADVERTENCIA: Este componente tiene una configuración especial.
     Lee esto antes de modificar cualquier cosa.

     PROBLEMA QUE RESUELVE:
     Flatpickr toma control del <input> y lo transforma internamente.
     Cada vez que Livewire hace un re-render del DOM, destruye el
     input de Flatpickr y el picker queda inútil (no abre, no responde).

     POR QUÉ wire:ignore EN EL DIV PADRE:
     wire:ignore le dice a Livewire "no toques este bloque del DOM",
     así Flatpickr sobrevive los re-renders y el picker sigue funcional.

     NUEVO PROBLEMA QUE GENERA:
     wire:ignore también bloquea que Alpine reaccione a cambios
     que vienen del servidor (ej: botones "Fecha Anterior / Posterior"
     del trait ConFechaReporteDia cambian $fecha desde PHP).
     El @entangle SÍ recibe el nuevo valor, pero $watch no se dispara
     porque el DOM no se re-renderiza.

     POR QUÉ Livewire.hook('commit'):
     Es el puente entre el servidor y Flatpickr. Cada vez que Livewire
     termina un ciclo de actualización, este hook lee el valor actualizado
     de @entangle y llama manualmente a picker.setDate() para sincronizar
     el picker con la fecha que vino del servidor.

     RESUMEN DE LA SOLUCIÓN:
     wire:ignore  →  Flatpickr no se destruye en cada render
     hook commit  →  Flatpickr se sincroniza cuando el servidor cambia la fecha
     @entangle    →  Sincroniza Alpine → Livewire cuando el usuario elige una fecha

     SI QUIERES MODIFICAR ALGO, TEN EN CUENTA:
     - No quites el wire:ignore o el picker dejará de funcionar tras interactuar.
     - No reemplaces el hook por $watch, no se dispara con wire:ignore activo.
     - No uses wire:model directamente en el input, Flatpickr lo reemplaza.
    ============================================================
    --}}
    x-data="{ 
        picker: null,
        value: @entangle($attributes->wire('model'))
    }"
    x-init="
        picker = flatpickr($refs.input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'Y-m-d',
            allowInput: true,
            defaultDate: value,
            onChange: function(selectedDates, dateStr) {
                value = dateStr;
            }
        });

        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                $nextTick(() => {
                    if (picker && value) {
                        picker.setDate(value, false);
                    }
                });
            });
        });
    "
    wire:ignore
>
    <x-input 
        type="text"
        x-ref="input"
        class="w-auto"
        {{ $attributes->except('wire:model') }}
    />
</div>