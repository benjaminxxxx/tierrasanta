<div class="flex flex-col h-full">
    <div class="flex-1 overflow-y-auto space-y-2 text-sm">
        @foreach ($messages as $msg)
            <div
                class="p-2 rounded-md
                {{ $msg['role'] === 'user' ? 'bg-indigo-100 text-right' : 'bg-gray-100' }}">
                {{ $msg['content'] }}
            </div>
        @endforeach
    </div>

    <div class="p-3 border-t flex gap-2">
        <input
            wire:model.defer="input"
            wire:keydown.enter="send"
            class="flex-1 border rounded-md px-3 py-2"
            placeholder="Escribe aquí…"
        />
        <button
            wire:click="send"
            class="bg-indigo-600 text-white px-4 rounded-md"
        >
            Enviar
        </button>
    </div>
</div>
