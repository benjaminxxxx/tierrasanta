<x-app-layout>
 
    <div class="flex justify-center w-full">
        <livewire:campo-mapa-component />
    </div>
    <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.11/dist/interact.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            //const asignarRiegoBtn = document.getElementById('asignar-riego-btn');
            const activarCambiarPosicion = document.getElementById('activar_cambiar_posicion');
            //let selectedCount = 0;

            function isDraggingEnabled() {
                return activarCambiarPosicion.checked;
            }

            activarCambiarPosicion.addEventListener('change', () => {
                document.querySelectorAll('.campo').forEach(campo => {
                    if (isDraggingEnabled()) {
                        // Agregar la clase para cambiar el cursor a "move"
                        campo.classList.add('cursor-move');
                        campo.classList.remove('cursor-default');
                    } else {
                        // Cambiar el cursor a normal y quitar la clase "move"
                        campo.classList.remove('cursor-move');
                        campo.classList.add('cursor-default');
                    }
                });
            });

            interact('.campo').styleCursor(false).draggable({
                // Activar arrastre
                listeners: {
                    move(event) {

                        if (!isDraggingEnabled()) return;

                        // Obtener la posición actual del elemento
                        let target = event.target;
                        let x = (parseFloat(target.style.left) || 0) + event.dx;
                        let y = (parseFloat(target.style.top) || 0) + event.dy;
                        x = Math.round(x);
                        y = Math.round(y);
                        // Mover el elemento a la nueva posición usando left y top
                        target.style.left = x + 'px';
                        target.style.top = y + 'px';
                    },
                    end(event) {

                        if (!isDraggingEnabled()) return;

                        // Obtener las coordenadas finales después del arrastre
                        let target = event.target;
                        let x = parseFloat(target.style.left) || 0;
                        let y = parseFloat(target.style.top) || 0;
                        x = Math.round(x);
                        y = Math.round(y);
                        // Guardar la posición en la base de datos
                        const nombre = target.getAttribute('data-nombre');
                        savePosition(nombre, x, y);
                    }
                },
                styleCursor: false
            });

            function savePosition(nombre, x, y) {
                fetch(`/campo/mapa/guardar-posicion/${nombre}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            pos_x: x,
                            pos_y: y
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.reload) {
                            // Recargar la página si se necesita actualizar los campos
                            Livewire.dispatch('posicionActualizada');
                        }
                    })
                    .catch(error => {
                        console.log(error);
                    });
            }
        });
    </script>
</x-app-layout>
