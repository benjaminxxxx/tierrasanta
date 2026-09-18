// resources/js/plugins/SumadorSeleccionPlugin.js

export class SumadorSeleccionPlugin
    extends window.Handsontable.plugins.BasePlugin
{
    constructor(hotInstance) {
        super(hotInstance);
        this.columnaAgruparIndex = 1;
        this.columnasOmitir = [0, 1];
    }

    isEnabled() {
        return !!this.hot.getSettings().sumadorSeleccion;
    }
    enablePlugin() {
        if (this.enabled) return;

        const options = this.hot.getSettings().sumadorSeleccion;
        if (typeof options === "object") {
            if (options.columnaAgrupar !== undefined)
                this.columnaAgruparIndex = options.columnaAgrupar;
            else if (options.columnaNombre !== undefined)
                this.columnaAgruparIndex = options.columnaNombre;

            if (options.columnasOmitir !== undefined)
                this.columnasOmitir = options.columnasOmitir;
        }

        const itemSuma = {
            key: "sumar_seleccion_custom",
            name: "Calcular Suma de Selección",
            callback: () => this.ejecutarSuma(),
            disabled: () => !this.tieneRangoSeleccionado(),
        };

        // 1. Inyección para tablas con contextMenu: true
        this.addHook("afterContextMenuDefaultOptions", (defaultOptions) => {
            if (defaultOptions && Array.isArray(defaultOptions.items)) {
                const yaExiste = defaultOptions.items.some(
                    (item) => item.key === "sumar_seleccion_custom",
                );
                if (!yaExiste) {
                    defaultOptions.items.push(itemSuma);
                }
            }
        });

        // 2. Inyección para tablas con contextMenu personalizado (Objeto con items)
        this.addHook("beforeContextMenuShow", (contextMenuPlugin) => {
            if (!contextMenuPlugin) return;

            // Si la tabla usa un objeto personalizado de items
            const rawContextMenuSetting = this.hot.getSettings().contextMenu;

            if (
                typeof rawContextMenuSetting === "object" &&
                rawContextMenuSetting !== null
            ) {
                const itemsObj = rawContextMenuSetting.items;

                if (itemsObj && typeof itemsObj === "object") {
                    // Inyectamos la clave si no fue declarada explícitamente en la vista
                    if (!itemsObj["sumar_seleccion_custom"]) {
                        itemsObj["sumar_seleccion_custom"] = itemSuma;
                    }
                }
            }
        });

        super.enablePlugin();
    }

    disablePlugin() {
        super.disablePlugin();
    }

    tieneRangoSeleccionado() {
        const selections = this.hot.getSelected();
        if (!selections || selections.length === 0) return false;
        if (selections.length > 1) return true;
        const [startRow, startCol, endRow, endCol] = selections[0];
        return startRow !== endRow || startCol !== endCol;
    }
    ejecutarSuma() {
        const selectedRanges = this.hot.getSelected();
        if (!selectedRanges || selectedRanges.length === 0) return;

        const celdasProcesadas = new Set();
        const mapaFilas = new Map();
        const mapaColumnas = new Map();
        let granTotal = 0;

        selectedRanges.forEach(([startRow, startCol, endRow, endCol]) => {
            const rMin = Math.min(startRow, endRow);
            const rMax = Math.max(startRow, endRow);
            const cMin = Math.min(startCol, endCol);
            const cMax = Math.max(startCol, endCol);

            for (let r = rMin; r <= rMax; r++) {
                for (let c = cMin; c <= cMax; c++) {
                    if (this.columnasOmitir.includes(c)) continue;

                    const cellKey = `${r}_${c}`;
                    if (celdasProcesadas.has(cellKey)) continue;
                    celdasProcesadas.add(cellKey);

                    if (!mapaColumnas.has(c)) {
                        const propOrHeader = this.hot.getColHeader(c);
                        mapaColumnas.set(c, {
                            index: c,
                            titulo:
                                typeof propOrHeader === "string" && propOrHeader
                                    ? propOrHeader
                                    : `Col ${c + 1}`,
                            total: 0,
                        });
                    }

                    if (!mapaFilas.has(r)) {
                        let nombreTrabajador = this.hot.getDataAtCell(
                            r,
                            this.columnaAgruparIndex,
                        );
                        if (
                            !nombreTrabajador ||
                            String(nombreTrabajador).trim() === ""
                        ) {
                            nombreTrabajador = `Fila ${r + 1}`;
                        }
                        mapaFilas.set(r, {
                            nombre: String(nombreTrabajador).trim(),
                            celdas: {},
                            totalFila: 0,
                        });
                    }

                    const valRaw = this.hot.getDataAtCell(r, c);
                    let valNum = null;

                    if (
                        valRaw !== null &&
                        valRaw !== undefined &&
                        valRaw !== "" &&
                        valRaw !== "-"
                    ) {
                        if (typeof valRaw === "number") {
                            valNum = valRaw;
                        } else {
                            const parsed = parseFloat(
                                String(valRaw).replace(/[^0-9.-]+/g, ""),
                            );
                            valNum = isNaN(parsed) ? null : parsed;
                        }
                    }

                    if (valNum !== null) {
                        const filaObj = mapaFilas.get(r);
                        const colObj = mapaColumnas.get(c);

                        filaObj.celdas[c] = valNum;
                        filaObj.totalFila += valNum;
                        colObj.total += valNum;
                        granTotal += valNum;
                    }
                }
            }
        });

        // Formateo seguro para evitar errores de .toFixed en valores undefined/NaN
        const columnas = Array.from(mapaColumnas.values()).sort(
            (a, b) => a.index - b.index,
        );
        const totalesColumnas = {};

        columnas.forEach((c) => {
            const valTotalCol = c.total || 0;
            totalesColumnas[c.index] = Number(valTotalCol.toFixed(2));
        });

        const filas = Array.from(mapaFilas.values()).map((f) => {
            const valTotalFila = f.totalFila || 0;
            return {
                nombre: f.nombre,
                celdas: f.celdas,
                totalFila: Number(valTotalFila.toFixed(2)),
            };
        });

        const granTotalSeguro = granTotal || 0;

        this.renderizarModal({
            columnas,
            filas,
            totalesColumnas,
            granTotal: Number(granTotalSeguro.toFixed(2)),
        });
    }
    renderizarModal(data) {
        const modalExistente = document.getElementById("modal-sumador-hot");
        if (modalExistente) modalExistente.remove();

        // Isagana ti panag-destructure kadagiti variables tapno awan ti ReferenceError
        const {
            columnas = [],
            filas = [],
            totalesColumnas = {},
            granTotal = 0,
        } = data || {};

        const isDark = document.documentElement.classList.contains("dark");

        // Configuración de colores con fallbacks explícitos
        const bgModal = isDark
            ? "bg-slate-900 text-slate-100 border-slate-700"
            : "bg-white text-gray-800 border-gray-200";
        const bgHeader = isDark
            ? "bg-slate-800 border-slate-700"
            : "bg-gray-100 border-gray-200";
        const bgCell = isDark
            ? "bg-slate-800/80 text-slate-100"
            : "bg-gray-50 text-gray-800";
        const borderRow = isDark ? "border-slate-800" : "border-gray-200";

        const headersHtml = columnas
            .map(
                (c) =>
                    `<th class="px-3 py-2 border ${borderRow} text-center font-bold">${c.titulo}</th>`,
            )
            .join("");

        const bodyHtml = filas
            .map((f) => {
                const celdasHtml = columnas
                    .map((c) => {
                        const val = f.celdas ? f.celdas[c.index] : null;

                        // Se verifica explícitamente que sea un número válido
                        const esNumeroValido =
                            typeof val === "number" && !isNaN(val);

                        return `
                        <td class="px-2 py-1.5 text-center whitespace-nowrap">
                            ${
                                esNumeroValido
                                    ? `<span class="px-1.5 py-0.5 rounded ${bgCell}">${val.toFixed(2)}</span>`
                                    : '<span class="opacity-30">-</span>'
                            }
                        </td>
                    `;
                    })
                    .join("");

                const totalFilaNum =
                    typeof f.totalFila === "number" && !isNaN(f.totalFila)
                        ? f.totalFila
                        : 0;

                return `
                <tr class="hover:bg-slate-500/10">
                    <td class="px-3 py-1.5 border ${borderRow} font-medium text-xs">${f.nombre}</td>
                    ${celdasHtml}
                    <td class="px-3 py-1.5 border ${borderRow} text-right font-bold font-mono text-emerald-500">${totalFilaNum.toFixed(2)}</td>
                </tr>
            `;
            })
            .join("");

        const footHtml = columnas
            .map((c) => {
                const valCol = totalesColumnas[c.index];
                const esNumeroValido =
                    typeof valCol === "number" && !isNaN(valCol);
                return `<td class="px-3 py-2 border ${borderRow} text-center font-mono font-bold">${esNumeroValido ? valCol.toFixed(2) : "0.00"}</td>`;
            })
            .join("");

        const granTotalNum =
            typeof granTotal === "number" && !isNaN(granTotal) ? granTotal : 0;
        const granTotalFormatted = granTotalNum.toFixed(2);

        const modalDom = document.createElement("div");
        modalDom.id = "modal-sumador-hot";

        // Estilos de superposición directa + z-index extremo para superar Handsontable
        modalDom.style.cssText =
            "position: fixed; inset: 0; z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px);";

        modalDom.innerHTML = `
        <div class="relative w-full max-w-4xl rounded-xl shadow-2xl border ${bgModal} overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b ${bgHeader} flex justify-between items-center">
                <h3 class="text-base font-bold">Detalle de Suma Calculada</h3>
                <button id="btn-cerrar-modal-hot" class="text-gray-400 hover:text-red-500 font-bold text-xl leading-none cursor-pointer">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4">
                <div class="flex items-center justify-between p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                    <div>
                        <span class="text-xs uppercase font-bold tracking-wider text-emerald-500 block">Gran Total Sumado</span>
                        <span class="text-xs opacity-70">Suma total de la selección realizada</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-500">${granTotalFormatted}</div>
                </div>

                <div class="overflow-x-auto border ${borderRow} rounded-lg max-h-72">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="${bgHeader}">
                                <th class="px-3 py-2 border ${borderRow} font-bold">Trabajador / Fila</th>
                                ${headersHtml}
                                <th class="px-3 py-2 border ${borderRow} text-right font-bold">Total Fila</th>
                            </tr>
                        </thead>
                        <tbody>${bodyHtml}</tbody>
                        <tfoot>
                            <tr class="${bgHeader} font-bold">
                                <td class="px-3 py-2 border ${borderRow} uppercase">Total Columna</td>
                                ${footHtml}
                                <td class="px-3 py-2 border ${borderRow} text-right font-mono text-sm bg-emerald-600 text-white font-black">${granTotalFormatted}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t ${bgHeader} flex justify-end">
                <button id="btn-aceptar-modal-hot" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs transition-colors cursor-pointer">
                    Aceptar
                </button>
            </div>
        </div>
    `;

        document.body.appendChild(modalDom);

        const destruirModal = () => modalDom.remove();
        document.getElementById("btn-cerrar-modal-hot").onclick = destruirModal;
        document.getElementById("btn-aceptar-modal-hot").onclick =
            destruirModal;
        modalDom.onclick = (e) => {
            if (e.target === modalDom) destruirModal();
        };
    }
}
