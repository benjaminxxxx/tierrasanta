{{-- Archivo del documento del contrato (wire:model="archivo"): PDF o imagen, con su estado de carga. --}}
<label class="flex cursor-pointer items-center gap-2 rounded-[10px] border border-dashed border-mist bg-paper px-3 py-2.5 text-sm text-graphite hover:bg-fog">
    <i class="fa fa-paperclip text-steel"></i>
    <span class="min-w-0 flex-1 truncate">
        @if ($archivo)
            {{ $archivo->getClientOriginalName() }}
            <span class="text-xs text-pewter">({{ number_format($archivo->getSize() / 1024, 0) }} KB)</span>
        @else
            Elegir PDF o foto (máx. 10 MB)
        @endif
    </span>
    <span wire:loading wire:target="archivo" class="text-xs text-pewter"><i class="fa fa-spinner fa-spin"></i> Subiendo…</span>
    <input type="file" wire:model="archivo" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*">
</label>
@error('archivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
