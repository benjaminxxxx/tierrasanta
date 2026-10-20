import "./bootstrap";
import Handsontable from "./handsontable";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import "flowbite";

import { Spanish } from "flatpickr/dist/l10n/es.js"; // ✅ importa el idioma
import { Calendar } from "@fullcalendar/core";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import { SumadorSeleccionPlugin } from "./plugins/SumadorSeleccionPlugin";
import searchableSelect from "./components/searchable-select";
import campoFecha from "./components/campo-fecha";

// <x-searchable-select>: se registra una sola vez (antes cada variante traía su propio <script>)
// <x-fecha>: fecha escrita con máscara dd/mm/aaaa o elegida en el almanaque
const registrarComponentes = (Alpine) => {
    Alpine.data("searchableSelect", searchableSelect);
    Alpine.data("campoFecha", campoFecha);
};
if (window.Alpine) {
    registrarComponentes(window.Alpine);
} else {
    document.addEventListener("alpine:init", () => registrarComponentes(window.Alpine));
}

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

Handsontable.plugins.registerPlugin("SumadorSeleccion", SumadorSeleccionPlugin);

window.HstConfig = {
    datePickerConfig: {
        i18n: {
            previousMonth: "Mes anterior",
            nextMonth: "Mes siguiente",
            months: [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ],
            weekdays: [
                "Domingo",
                "Lunes",
                "Martes",
                "Miércoles",
                "Jueves",
                "Viernes",
                "Sábado",
            ],
            weekdaysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
        },
        firstDay: 1,
    },
    language: "es-MX",
    colHeaders: true,
    rowHeaders: true,
    width: '100%',
    licenseKey: "non-commercial-and-evaluation",
    manualColumnResize: false,
    manualRowResize: true,
    stretchH: "all",
    //minSpareRows: 1,
    autoColumnSize: false,
};

// Mientras se escribe en un campo que se actualiza en vivo (wire:model.live…) o en un buscador (<x-searchable-select>),
// el <x-loading> de pantalla completa no aparece: tapaba el campo y hacía perder el foco. Esos campos muestran su
// propio indicador de carga (spinner al final del <x-input>, "Buscando…" del searchable-select).
const esCampoEnVivo = (el) =>
    el instanceof HTMLElement &&
    (el.matches("input, textarea") || el.isContentEditable) &&
    (el.getAttributeNames().some((n) => n.startsWith("wire:model.live")) ||
        el.hasAttribute("data-campo-en-vivo") ||
        el.closest('[x-data^="searchableSelect"]') !== null);
document.addEventListener("focusin", (e) => document.body.classList.toggle("escribiendo-en-vivo", esCampoEnVivo(e.target)));
document.addEventListener("focusout", () => document.body.classList.remove("escribiendo-en-vivo"));
