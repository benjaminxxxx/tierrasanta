import "./bootstrap";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "flowbite";

import { Spanish } from "flatpickr/dist/l10n/es.js"; // ✅ importa el idioma
import { Calendar } from "@fullcalendar/core";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";

import Handsontable from "handsontable";
import "handsontable/styles/handsontable.min.css";
import "handsontable/styles/ht-theme-main.min.css";
import { registerLanguageDictionary, esMX } from "handsontable/i18n";
registerLanguageDictionary(esMX);

window.Handsontable = Handsontable;

// Establece el idioma globalmente
flatpickr.localize(Spanish);
flatpickr.setDefaults({
    dateFormat: "d/m/Y", // ✅ visible al usuario
    altInput: true,
    altFormat: "d/m/Y", // ✅ formato visible en input
    allowInput: true,
});

window.FullCalendar = {
    Calendar,
    resourceTimelinePlugin,
};
flatpickr(".datepicker", {
    //mode: "range",
});
// ==========================================
// 2. DEFINIR Y REGISTRAR EL PLUGIN
// ==========================================
class SumadorSeleccionPlugin extends Handsontable.plugins.BasePlugin {
    constructor(hotInstance) {
        super(hotInstance);
        this.columnaNombreIndex = 1;
        this.columnasOmitir = [0, 1];
    }

    isEnabled() {
        return !!this.hot.getSettings().sumadorSeleccion;
    }

    enablePlugin() {
        if (this.enabled) return;

        const options = this.hot.getSettings().sumadorSeleccion;
        if (typeof options === "object") {
            if (options.columnaNombre !== undefined) this.columnaNombreIndex = options.columnaNombre;
            if (options.columnasOmitir !== undefined) this.columnasOmitir = options.columnasOmitir;
        }

        this.addContextMenuOption();
        super.enablePlugin();
    }

    addContextMenuOption() {
        const settings = this.hot.getSettings();
        let contextMenu = settings.contextMenu;

        if (contextMenu === true) {
            contextMenu = { items: {} };
        } else if (!contextMenu) {
            contextMenu = { items: {} };
        }

        let items = contextMenu.items || {};

        items["sumar_seleccion_matriz"] = {
            name: "Calcular Suma de Selección",
            callback: () => this.ejecutarSuma(),
            disabled: () => !this.tieneRangoSeleccionado(),
        };

        this.hot.updateSettings({ contextMenu: { ...contextMenu, items } });
    }

    tieneRangoSeleccionado() {
        const selections = this.hot.getSelected();
        if (!selections || selections.length === 0) return false;
        if (selections.length > 1) return true;
        const [startRow, startCol, endRow, endCol] = selections[0];
        return startRow !== endRow || startCol !== endCol;
    }

    ejecutarSuma() {
        const selections = this.hot.getSelected();
        if (!selections || selections.length === 0) return;

        const celdasSeleccionadas = new Set();
        const filasMap = new Map();
        const columnasSet = new Set();
        const titulosColumnasMap = new Map();

        selections.forEach(([startRow, startCol, endRow, endCol]) => {
            const rMin = Math.min(startRow, endRow);
            const rMax = Math.max(startRow, endRow);
            const cMin = Math.min(startCol, endCol);
            const cMax = Math.max(startCol, endCol);

            for (let r = rMin; r <= rMax; r++) {
                for (let c = cMin; c <= cMax; c++) {
                    celdasSeleccionadas.add(`${r}-${c}`);
                }
            }
        });

        celdasSeleccionadas.forEach((coord) => {
            const [r, c] = coord.split("-").map(Number);

            if (this.columnasOmitir.includes(c)) return;

            const rowData = this.hot.getSourceDataAtRow(r);
            if (rowData && rowData.tipo === "TOTAL") return;

            const nombreFila = this.hot.getDataAtCell(r, this.columnaNombreIndex) || `Fila ${r + 1}`;
            const colHeader = this.hot.getColHeader(c);

            columnasSet.add(c);
            titulosColumnasMap.set(c, colHeader);

            if (!filasMap.has(r)) {
                filasMap.set(r, { nombre: nombreFila, valores: {} });
            }

            const filaObj = filasMap.get(r);
            const rawVal = this.hot.getDataAtCell(r, c);
            const valNum = parseFloat(rawVal);

            if (!isNaN(valNum)) {
                filaObj.valores[c] = (filaObj.valores[c] || 0) + valNum;
            }
        });

        const columnas = Array.from(columnasSet)
            .sort((a, b) => a - b)
            .map((colIndex) => ({
                index: colIndex,
                titulo: titulosColumnasMap.get(colIndex) || `Col ${colIndex}`,
                totalColumna: 0,
            }));

        let granTotal = 0;
        const filasFinales = [];

        filasMap.forEach((filaObj) => {
            let totalFila = 0;
            const celdasValores = {};

            columnas.forEach((col) => {
                const val = filaObj.valores[col.index] ?? null;
                if (val !== null) {
                    celdasValores[col.index] = val;
                    totalFila += val;
                    col.totalColumna += val;
                } else {
                    celdasValores[col.index] = null;
                }
            });

            if (totalFila > 0 || Object.values(celdasValores).some((v) => v !== null)) {
                granTotal += totalFila;
                filasFinales.push({
                    nombre: filaObj.nombre,
                    celdas: celdasValores,
                    totalFila: totalFila.toFixed(2),
                });
            }
        });

        const totalesColumnas = {};
        columnas.forEach((col) => {
            totalesColumnas[col.index] = col.totalColumna.toFixed(2);
        });

        this.renderizarModal({
            columnas,
            filas: filasFinales,
            totalesColumnas,
            granTotal: granTotal.toFixed(2),
        });
    }

    renderizarModal(data) {
        const modalExistente = document.getElementById("modal-sumador-hot");
        if (modalExistente) modalExistente.remove();

        const isDark = document.documentElement.classList.contains("dark");
        const bgModal = isDark ? "bg-slate-900 text-slate-100 border-slate-700" : "bg-white text-gray-800 border-gray-200";
        const bgHeader = isDark ? "bg-slate-800 border-slate-700" : "bg-gray-100 border-gray-200";
        const bgCell = isDark ? "bg-slate-800/50" : "bg-gray-50";

        const headersHtml = data.columnas.map((c) => `<th class="px-3 py-2 border text-center font-bold">${c.titulo}</th>`).join("");

        const bodyHtml = data.filas
            .map((f) => {
                const celdasHtml = data.columnas
                    .map((c) => {
                        const val = f.celdas[c.index];
                        return `<td class="px-3 py-1.5 border text-center font-mono">${
                            val !== null
                                ? `<span class="px-1.5 py-0.5 rounded ${bgCell}">${val.toFixed(2)}</span>`
                                : '<span class="opacity-30">-</span>'
                        }</td>`;
                    })
                    .join("");

                return `
                <tr class="hover:bg-slate-500/10">
                    <td class="px-3 py-1.5 border font-medium text-xs">${f.nombre}</td>
                    ${celdasHtml}
                    <td class="px-3 py-1.5 border text-right font-bold font-mono text-emerald-500">${f.totalFila}</td>
                </tr>
            `;
            })
            .join("");

        const footHtml = data.columnas.map((c) => `<td class="px-3 py-2 border text-center font-mono font-bold">${data.totalesColumnas[c.index]}</td>`).join("");

        const modalDom = document.createElement("div");
        modalDom.id = "modal-sumador-hot";
        modalDom.className = "fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm";

        modalDom.innerHTML = `
            <div class="relative w-full max-w-4xl rounded-xl shadow-2xl border ${bgModal} overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 border-b ${bgHeader} flex justify-between items-center">
                    <h3 class="text-base font-bold">Detalle de Suma Calculada</h3>
                    <button id="btn-cerrar-modal-hot" class="text-gray-400 hover:text-red-500 font-bold text-xl leading-none">&times;</button>
                </div>
                <div class="p-6 overflow-y-auto space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-emerald-500 block">Gran Total Sumado</span>
                            <span class="text-xs opacity-70">Suma total de la selección realizada</span>
                        </div>
                        <div class="text-3xl font-black text-emerald-500">${data.granTotal}</div>
                    </div>

                    <div class="overflow-x-auto border rounded-lg max-h-72">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="${bgHeader}">
                                    <th class="px-3 py-2 border font-bold">Trabajador / Fila</th>
                                    ${headersHtml}
                                    <th class="px-3 py-2 border text-right font-bold">Total Fila</th>
                                </tr>
                            </thead>
                            <tbody>${bodyHtml}</tbody>
                            <tfoot>
                                <tr class="${bgHeader} font-bold">
                                    <td class="px-3 py-2 border uppercase">Total Columna</td>
                                    ${footHtml}
                                    <td class="px-3 py-2 border text-right font-mono text-sm bg-emerald-600 text-white font-black">${data.granTotal}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="px-6 py-3 border-t ${bgHeader} flex justify-end">
                    <button id="btn-aceptar-modal-hot" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs transition-colors">
                        Aceptar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modalDom);

        const destruirModal = () => modalDom.remove();
        document.getElementById("btn-cerrar-modal-hot").onclick = destruirModal;
        document.getElementById("btn-aceptar-modal-hot").onclick = destruirModal;
        modalDom.onclick = (e) => {
            if (e.target === modalDom) destruirModal();
        };
    }
}

// Registrar el Plugin con Handsontable importado de NPM
Handsontable.plugins.registerPlugin("SumadorSeleccion", SumadorSeleccionPlugin);

// ==========================================
// 3. CONFIGURACIÓN GLOBAL
// ==========================================
window.HstConfig = {
    datePickerConfig: {
        i18n: {
            previousMonth: "Mes anterior",
            nextMonth: "Mes siguiente",
            months: [
                "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ],
            weekdays: ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"],
            weekdaysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
        },
        firstDay: 1,
    },
    language: "es-MX",
    colHeaders: true,
    rowHeaders: true,
    width: "100%",
    licenseKey: "non-commercial-and-evaluation",
    manualColumnResize: false,
    manualRowResize: true,
    stretchH: "all",
    autoColumnSize: false,
    sumadorSeleccion: {
        columnaNombre: 1,
        columnasOmitir: [0, 1],
    },
};