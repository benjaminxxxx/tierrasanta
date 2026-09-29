<dialog x-ref="divideModal"
    class="w-full max-w-md rounded-xl bg-slate-900 text-white backdrop:bg-black/60 p-0 border border-slate-700 shadow-2xl">
    {{-- Header --}}
    <div class="border-b border-slate-700 p-6">
        <h3 class="text-lg font-bold">✂️ Dividir salida</h3>
        <p class="text-sm text-slate-400 mt-1">
            Total:
            <span class="font-semibold text-slate-300">
                <span x-text="salidaSeleccionada?.cantidad?.toFixed(2)"></span> kg
            </span>
        </p>
    </div>

    {{-- Body --}}
    <div class="p-6 space-y-6">
        {{-- Parte 1 --}}
        <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-300">
                Parte 1
            </label>

            <input type="number" step="0.01" min="0" x-model.number="division.cantidad1"
                @input="
                    division.cantidad1 = Math.min(
                        salidaSeleccionada.cantidad,
                        Math.max(0, division.cantidad1)
                    )
                "
                class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 focus:border-blue-500 focus:outline-none" />

            <div class="flex justify-between text-xs text-slate-500">
                <span>kg</span>
                <span
                    x-text="(
        ((division.cantidad1 || 0) / (salidaSeleccionada?.cantidad || 1)) * 100
    ).toFixed(0) + '%'"></span>
            </div>
        </div>

        {{-- Separador --}}
        <div class="flex items-center gap-2 text-slate-500">
            <div class="flex-1 h-px bg-slate-700"></div>
            <span class="text-xs">+</span>
            <div class="flex-1 h-px bg-slate-700"></div>
        </div>

        {{-- Parte 2 --}}
        <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-300">
                Parte 2
            </label>

            <input type="number" step="0.01" disabled
                class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-slate-400 cursor-not-allowed"
                :value="((salidaSeleccionada?.cantidad || 0) - (division.cantidad1 || 0)).toFixed(2)" />


            <div class="flex justify-between text-xs text-slate-500">
                <span>kg (automático)</span>
                <span
                    x-text="(
        (
            ((salidaSeleccionada?.cantidad || 0) - (division.cantidad1 || 0))
            / (salidaSeleccionada?.cantidad || 1)
        ) * 100
    ).toFixed(0) + '%'"></span>

            </div>
        </div>

        {{-- Validación --}}
        <template x-if="divisionInvalida">
            <div class="p-3 rounded-lg bg-red-900/30 border border-red-700/50">
                <p class="text-xs text-red-300">
                    Ambas partes deben ser mayores a 0
                </p>
            </div>
        </template>
    </div>

    {{-- Footer --}}
    <div class="border-t border-slate-700 p-6 flex gap-3">
        <button type="button" @click="$refs.divideModal.close()"
            class="flex-1 rounded-lg border border-slate-600 bg-slate-800/40 px-4 py-2 text-slate-300 hover:text-white hover:border-slate-500">
            Cancelar
        </button>

        <button type="button" :disabled="divisionInvalida"
            @click="confirmarDivision()"
            class="flex-1 rounded-lg bg-amber-600 hover:bg-amber-500 px-4 py-2 font-medium text-white disabled:opacity-50 disabled:cursor-not-allowed">
            Dividir
        </button>
    </div>
</dialog>
