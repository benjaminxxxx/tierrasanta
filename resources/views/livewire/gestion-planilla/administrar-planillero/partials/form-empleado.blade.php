<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <x-group-field>
        <x-label for="nombres">Nombres</x-label>
        <x-input type="text" wire:model="nombres" class="uppercase" id="nombres" />
        <x-input-error for="nombres" />
    </x-group-field>

    <x-group-field>
        <x-label for="apellido_paterno">Apellido Paterno</x-label>
        <x-input type="text" class="uppercase" wire:model="apellido_paterno" id="apellido_paterno" />
        <x-input-error for="apellido_paterno" />
    </x-group-field>

    <x-group-field>
        <x-label for="apellido_materno">Apellido Materno</x-label>
        <x-input type="text" class="uppercase" wire:model="apellido_materno" id="apellido_materno" />
        <x-input-error for="apellido_materno" />
    </x-group-field>

    <x-group-field>
        <x-label for="documento">Documento</x-label>
        <x-input type="text" class="uppercase" wire:model="documento" id="documento" />
        <x-input-error for="documento" />
    </x-group-field>

    <x-group-field>
        <x-label for="email">Email</x-label>
        <x-input type="text" class="uppercase" wire:model="email" id="email" />
    </x-group-field>

    <x-group-field>
        <x-label for="direccion">Dirección</x-label>
        <x-input type="text" class="uppercase" wire:model="direccion" id="direccion" />
    </x-group-field>

    <x-select class="uppercase" label="Género" wire:model="genero" id="genero" class="!w-full">
        <option value="M">Masculino</option>
        <option value="F">Femenino</option>
    </x-select>

    <x-group-field>
        <x-label for="fecha_nacimiento">Fecha de Nacimiento</x-label>
        <x-input type="date" autocomplete="off" wire:model="fecha_nacimiento" class="uppercase" id="fecha_nacimiento" />
        <x-input-error for="fecha_nacimiento" />
    </x-group-field>
    <x-group-field>
        <x-label for="fecha_ingreso">Fecha de Ingreso</x-label>
        <x-input type="date" autocomplete="off" wire:model="fecha_ingreso" class="uppercase" id="fecha_ingreso" />
        <x-input-error for="fecha_ingreso" />
    </x-group-field>

</div>