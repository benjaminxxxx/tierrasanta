<div x-data="{ openarchivoBackupHoyDialog() { $refs.archivoBackupHoyInput.click() } }">
    
        <!-- Botón para abrir el diálogo de archivos -->
        <a @click.prevent="openarchivoBackupHoyDialog()" href="#"
            class="block px-4 py-2 whitespace-nowrap">
            Restaurar Backup {{ $fecha }}
        </a>
        <input type="file"
            accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            x-ref="archivoBackupHoyInput" style="display: none;"
            wire:model.live="archivoBackupHoy" />
    
</div>
